<?php
// Leave.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// ── Initial load: current year ───────────────────────────────────────────────
$currentYear = date('Y');
$defaultFrom = $currentYear . '-01-01';
$defaultTo   = $currentYear . '-12-31';

$dbLeave = getDB();

$sqlLeave = "
    SELECT
        a.app_ID,
        a.DOF,
        a.TOF,
        a.NOD,
        a.Inclusive_Dates,
        a.Status,
        a.Remarks,
        a.DOL_A,
        a.DOL_B,
        a.DOL_C,
        a.lt_ID,
        l.Description AS LeaveDescription
    FROM tblappleave a
    LEFT JOIN tbl_lt l ON a.lt_ID = l.lt_ID
    WHERE a.emp_ID = ?
      AND a.DOF BETWEEN ? AND ?
    ORDER BY
        FIELD(LOWER(a.Status),
              'pending approval',
              'hr approved',
              'management approved',
              'disapproved'),
        a.DOF DESC,
        a.TOF DESC
";

$stmtLeave = $dbLeave->prepare($sqlLeave);
$stmtLeave->bind_param('iss', $_SESSION['emp_ID'], $defaultFrom, $defaultTo);
$stmtLeave->execute();
$resLeave = $stmtLeave->get_result();

// Helper: map a status string → CSS class
function leaveStatusClass(string $status): string {
    switch (strtolower($status)) {
        case 'pending approval':    return 'status-pending';
        case 'hr approved':         return 'status-hr-approved';
        case 'management approved': return 'status-mgmt-approved';
        case 'approved':            return 'status-approved';
        case 'disapproved':         return 'status-disapproved';
        default:                    return 'status-pending';
    }
}

// ── Leave types for the dropdown ─────────────────────────────────────────────
$dbLT  = getDB();
$resLT = $dbLT->query("SELECT lt_ID, Description FROM tbl_lt WHERE Type = 'Leave' ORDER BY Description");
$leaveTypes = [];
while ($lt = $resLT->fetch_assoc()) {
    $leaveTypes[] = $lt;
}
$dbLT->close();
?>

<style>
/* ── Header & filter bar ──────────────────────────────────────────────────── */
.leave-header {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
}

.leave-header h3 { margin: 0; white-space: nowrap; }
.leave-header .btn-new-leave { margin-left: auto; }

.leave-filter-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.leave-filter-bar label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #8a9ab5;
    white-space: nowrap;
}

.leave-filter-bar input[type="date"] {
    padding: 5px 8px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(138,154,181,.25);
    border-radius: 6px;
    color: #fff;
    font-size: 0.85rem;
    cursor: pointer;
}

.leave-filter-bar input[type="date"]:focus {
    outline: none;
    border-color: #0ea5a0;
    box-shadow: 0 0 0 2px rgba(14,165,160,.2);
}

.btn-filter {
    padding: 5px 14px;
    background: #0ea5a0;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background .2s;
}
.btn-filter:hover { background: #09bfb9; }

.btn-filter-reset {
    padding: 5px 14px;
    background: rgba(255,255,255,.06);
    color: #8a9ab5;
    border: 1px solid rgba(138,154,181,.25);
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background .2s;
}
.btn-filter-reset:hover { background: rgba(255,255,255,.1); color: #d4e4f7; }

.leave-loading {
    text-align: center;
    padding: 20px;
    color: #8a9ab5;
    font-size: 0.9rem;
    display: none;
}

/* ── Status chips ─────────────────────────────────────────────────────────── */
.status-chip {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
}
.status-pending       { background: rgba(245,200,66,.13);  color: #f5c842; border: 1px solid rgba(245,200,66,.3);  }
.status-hr-approved   { background: rgba(255,167,38,.13);  color: #ffa726; border: 1px solid rgba(255,167,38,.3);  }
.status-mgmt-approved { background: rgba(38,198,218,.13);  color: #26c6da; border: 1px solid rgba(38,198,218,.3);  }
.status-approved      { background: rgba(6,214,160,.13);   color: #06d6a0; border: 1px solid rgba(6,214,160,.3);   }
.status-disapproved   { background: rgba(255,107,107,.13); color: #ff8d8d; border: 1px solid rgba(255,107,107,.3); }

/* ── Leave card list ──────────────────────────────────────────────────────── */
.leave-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 0 0 8px;
}

.leave-item {
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(14,165,160,.14);
    border-radius: 14px;
    padding: 16px 18px;
    transition: border-color .2s, background .2s;
    position: relative;
}
.leave-item:hover {
    background: rgba(14,165,160,.05);
    border-color: rgba(14,165,160,.28);
}

/* Top row: particulars + status badge */
.leave-item-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
}

.leave-item-type {
    font-size: .95rem;
    font-weight: 700;
    color: #d4e4f7;
    line-height: 1.3;
}

/* Meta row: date filed, days, inclusive dates */
.leave-item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 18px;
    margin-bottom: 10px;
}

.leave-meta-pill {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: .78rem;
    color: #8a9ab5;
}

.leave-meta-pill span.meta-icon {
    font-size: .85rem;
    opacity: .7;
}

.leave-meta-pill strong {
    color: #b8cde4;
    font-weight: 600;
}

/* Remarks row */
.leave-item-remarks {
    font-size: .8rem;
    color: #8a9ab5;
    background: rgba(255,255,255,.03);
    border-left: 3px solid rgba(14,165,160,.35);
    border-radius: 0 6px 6px 0;
    padding: 6px 10px;
    margin-bottom: 12px;
    line-height: 1.4;
}
.leave-item-remarks span.remark-label {
    font-weight: 700;
    color: #0ea5a0;
    margin-right: 4px;
}

/* Action buttons row */
.leave-item-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.lv-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: .78rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: opacity .18s, transform .15s;
    white-space: nowrap;
}
.lv-btn:hover:not(:disabled) { transform: translateY(-1px); opacity: .88; }
.lv-btn:disabled { opacity: .3; cursor: not-allowed; }

.lv-btn-edit   { background: rgba(14,165,160,.15);  color: #0ea5a0;  border: 1px solid rgba(14,165,160,.3);  }
.lv-btn-delete { background: rgba(255,107,107,.15); color: #ff7b7b;  border: 1px solid rgba(255,107,107,.3); }
.lv-btn-print  { background: rgba(245,200,66,.12);  color: #f5c842;  border: 1px solid rgba(245,200,66,.28); }

/* empty state */
.leave-empty {
    text-align: center;
    padding: 40px 20px;
    color: #8a9ab5;
    font-size: .9rem;
}
.leave-empty-icon { font-size: 2.2rem; margin-bottom: 8px; opacity: .5; }

/* ── Responsive tweaks ────────────────────────────────────────────────────── */
@media (max-width: 500px) {
    .leave-item { padding: 14px; }
    .leave-item-type { font-size: .88rem; }
    .leave-header { flex-direction: column; align-items: flex-start; }
    .leave-header .btn-new-leave { margin-left: 0; width: 100%; justify-content: center; }
    .leave-filter-bar { width: 100%; }
    .leave-filter-bar input[type="date"] { flex: 1; min-width: 0; }
}
</style>

<div class="leave-card">

    <div class="leave-header">
        <h3>My Leave Applications</h3>

        <!-- ── AJAX date-range filter ── -->
        <div class="leave-filter-bar">
            <label for="lf_date_from">From</label>
            <input type="date" id="lf_date_from"
                   value="<?= htmlspecialchars($defaultFrom) ?>">

            <label for="lf_date_to">To</label>
            <input type="date" id="lf_date_to"
                   value="<?= htmlspecialchars($defaultTo) ?>">

            <button type="button" class="btn-filter"       onclick="leaveFilter()">Filter</button>
            <button type="button" class="btn-filter-reset" onclick="leaveFilterReset()">Refresh</button>
        </div>

        <button class="btn-new-leave" onclick="openLeaveModal()">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            New Leave Application
        </button>
    </div>

    <div class="leave-loading" id="leaveLoading">Loading…</div>

    <div class="leave-list" id="leaveTableBody">
    <?php
    $hasRows = false;
    while ($row = $resLeave->fetch_assoc()):
        $hasRows     = true;
        $statusRaw   = $row['Status'];
        $isPending   = (strtolower($statusRaw) === 'pending approval');
        $statusClass = leaveStatusClass($statusRaw);
        $rowData     = htmlspecialchars(json_encode([
            'app_ID'          => (int)$row['app_ID'],
            'lt_ID'           => (int)$row['lt_ID'],
            'dol_b'           => $row['DOL_B'],
            'dol_c'           => $row['DOL_C'] ?? '',
            'nod'             => $row['NOD'],
            'inclusive_dates' => $row['Inclusive_Dates'],
        ]), ENT_QUOTES, 'UTF-8');
    ?>
        <div class="leave-item" id="leave-row-<?= $row['app_ID'] ?>">

            <!-- Type + Status -->
            <div class="leave-item-top">
                <div class="leave-item-type">
                    <?= htmlspecialchars($row['LeaveDescription']) ?>
                </div>
                <span class="status-chip <?= $statusClass ?>">
                    <?= htmlspecialchars($statusRaw) ?>
                </span>
            </div>

            <!-- Meta info -->
            <div class="leave-item-meta">
                <div class="leave-meta-pill">
                    <span class="meta-icon">📅</span>
                    <span>Filed: <strong><?= date('M d, Y', strtotime($row['DOF'])) ?></strong>
                        <span style="opacity:.5;font-size:.72rem;">&nbsp;<?= htmlspecialchars($row['TOF']) ?></span>
                    </span>
                </div>
                <div class="leave-meta-pill">
                    <span class="meta-icon">🗓️</span>
                    <span>Inclusive: <strong><?= htmlspecialchars($row['Inclusive_Dates']) ?></strong></span>
                </div>
                <div class="leave-meta-pill">
                    <span class="meta-icon">⏱️</span>
                    <span>Days: <strong><?= htmlspecialchars($row['NOD']) ?></strong></span>
                </div>
            </div>

            <!-- Remarks -->
            <?php if (!empty($row['Remarks'])): ?>
            <div class="leave-item-remarks">
                <span class="remark-label">Remarks:</span>
                <?= htmlspecialchars($row['Remarks']) ?>
            </div>
            <?php endif; ?>

            <!-- Action buttons -->
            <div class="leave-item-actions">
                <?php if ($isPending): ?>
                <button class="lv-btn lv-btn-edit"
                        data-row="<?= $rowData ?>"
                        onclick="lmEditClick(this)">✏️ Edit</button>
                <button class="lv-btn lv-btn-delete"
                        onclick="deleteLeave(<?= (int)$row['app_ID'] ?>)">🗑️ Delete</button>
                <?php else: ?>
                <button class="lv-btn lv-btn-edit"   disabled>✏️ Edit</button>
                <button class="lv-btn lv-btn-delete" disabled>🗑️ Delete</button>
                <?php endif; ?>
                <button class="lv-btn lv-btn-print"
                        onclick="printLeave(<?= (int)$row['app_ID'] ?>)"
                        title="Print Leave Application">🖨️ Print</button>
            </div>
        </div>
    <?php endwhile; ?>

    <?php if (!$hasRows): ?>
        <div class="leave-empty">
            <div class="leave-empty-icon">📋</div>
            No leave applications found for this period.
        </div>
    <?php endif; ?>
    </div>

</div>

<script>
/* ── Status → CSS class ───────────────────────────────────────────────────── */
function leaveGetStatusClass(status) {
    switch ((status || '').toLowerCase()) {
        case 'pending approval':    return 'status-pending';
        case 'hr approved':         return 'status-hr-approved';
        case 'management approved': return 'status-mgmt-approved';
        case 'approved':            return 'status-approved';
        case 'disapproved':         return 'status-disapproved';
        default:                    return 'status-pending';
    }
}

/* ── Format YYYY-MM-DD → "Mon DD, YYYY" ─────────────────────────────────── */
function leaveFmtDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-US', { month:'short', day:'2-digit', year:'numeric' });
}

function esc(str) {
    return (str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ── Build a card <div> from a JSON row returned by get_leaves.php ─────────── */
function leaveBuildRow(row) {
    const isPending   = (row.Status || '').toLowerCase() === 'pending approval';
    const statusClass = leaveGetStatusClass(row.Status);

    const tmp = document.createElement('div');
    tmp.dataset.row = JSON.stringify({
        app_ID: row.app_ID, lt_ID: row.lt_ID,
        dol_b: row.DOL_B, dol_c: row.DOL_C || '',
        nod: row.NOD, inclusive_dates: row.Inclusive_Dates,
    });
    const safeRowData = tmp.getAttribute('data-row')
                           .replace(/&/g,'&amp;').replace(/"/g,'&quot;');

    const editBtn   = isPending
        ? `<button class="lv-btn lv-btn-edit" data-row="${safeRowData}" onclick="lmEditClick(this)">✏️ Edit</button>`
        : `<button class="lv-btn lv-btn-edit" disabled>✏️ Edit</button>`;
    const deleteBtn = isPending
        ? `<button class="lv-btn lv-btn-delete" onclick="deleteLeave(${parseInt(row.app_ID)})">🗑️ Delete</button>`
        : `<button class="lv-btn lv-btn-delete" disabled>🗑️ Delete</button>`;

    const remarksHtml = row.Remarks
        ? `<div class="leave-item-remarks"><span class="remark-label">Remarks:</span> ${esc(row.Remarks)}</div>`
        : '';

    const tofSmall = row.TOF
        ? `<span style="opacity:.5;font-size:.72rem;">&nbsp;${esc(row.TOF)}</span>`
        : '';

    return `
    <div class="leave-item" id="leave-row-${parseInt(row.app_ID)}">
        <div class="leave-item-top">
            <div class="leave-item-type">${esc(row.LeaveDescription)}</div>
            <span class="status-chip ${statusClass}">${esc(row.Status)}</span>
        </div>
        <div class="leave-item-meta">
            <div class="leave-meta-pill">
                <span class="meta-icon">📅</span>
                <span>Filed: <strong>${leaveFmtDate(row.DOF)}</strong>${tofSmall}</span>
            </div>
            <div class="leave-meta-pill">
                <span class="meta-icon">🗓️</span>
                <span>Inclusive: <strong>${esc(row.Inclusive_Dates)}</strong></span>
            </div>
            <div class="leave-meta-pill">
                <span class="meta-icon">⏱️</span>
                <span>Days: <strong>${esc(row.NOD)}</strong></span>
            </div>
        </div>
        ${remarksHtml}
        <div class="leave-item-actions">
            ${editBtn}
            ${deleteBtn}
            <button class="lv-btn lv-btn-print"
                    onclick="printLeave(${parseInt(row.app_ID)})"
                    title="Print Leave Application">🖨️ Print</button>
        </div>
    </div>`;
}

/* ── Core AJAX fetch ─────────────────────────────────────────────────────── */
async function leaveFetchRows(dateFrom, dateTo) {
    const loading = document.getElementById('leaveLoading');
    const list    = document.getElementById('leaveTableBody');

    loading.style.display    = 'block';
    list.style.opacity       = '0.4';
    list.style.pointerEvents = 'none';

    try {
        const params = new URLSearchParams({ date_from: dateFrom, date_to: dateTo });
        const res    = await fetch('Modules/Leave/get_leaves.php?' + params);
        if (!res.ok) throw new Error('Network error ' + res.status);

        const data = await res.json();
        if (data.error) throw new Error(data.error);

        list.innerHTML = data.rows.length
            ? data.rows.map(leaveBuildRow).join('')
            : `<div class="leave-empty">
                   <div class="leave-empty-icon">📋</div>
                   No records found for this period.
               </div>`;
    } catch (err) {
        list.innerHTML = `<div class="leave-empty" style="color:#ff9a9a;">
                              ⚠️ Error loading records. Please try again.
                          </div>`;
        console.error('leaveFilter error:', err);
    } finally {
        loading.style.display    = 'none';
        list.style.opacity       = '1';
        list.style.pointerEvents = '';
    }
}

/* ── Filter button ───────────────────────────────────────────────────────── */
function leaveFilter() {
    const from = document.getElementById('lf_date_from').value;
    const to   = document.getElementById('lf_date_to').value;
    if (!from || !to) { alert('Please select both From and To dates.'); return; }
    if (from > to)    { alert('"From" date cannot be later than "To" date.'); return; }
    leaveFetchRows(from, to);
}

/* ── Reset button ────────────────────────────────────────────────────────── */
function leaveFilterReset() {
    const year = new Date().getFullYear();
    const from = year + '-01-01';
    const to   = year + '-12-31';
    document.getElementById('lf_date_from').value = from;
    document.getElementById('lf_date_to').value   = to;
    leaveFetchRows(from, to);
}
</script>

<?php
$stmtLeave->close();
$dbLeave->close();
?>