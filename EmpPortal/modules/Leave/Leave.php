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
/* ── Leave table ──────────────────────────────────────────────────────────── */
.leave-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}

.leave-table th:nth-child(1), .leave-table td:nth-child(1) { width: 10%; }
.leave-table th:nth-child(2), .leave-table td:nth-child(2) { width: 13%; }
.leave-table th:nth-child(3), .leave-table td:nth-child(3) { width: 20%; }
.leave-table th:nth-child(4), .leave-table td:nth-child(4) { width: 9%;  }
.leave-table th:nth-child(5), .leave-table td:nth-child(5) { width: 18%; }
.leave-table th:nth-child(6), .leave-table td:nth-child(6) { width: 13%; }
.leave-table th:nth-child(7), .leave-table td:nth-child(7) { width: 17%; }

.leave-table thead th {
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}

.leave-table tbody td {
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.leave-table tbody td:nth-child(3),
.leave-table tbody td:nth-child(7) { text-align: left; }

.leave-table .action-buttons {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 4px;
    flex-wrap: nowrap;
}

/* ── Status chips ─────────────────────────────────────────────────────────── */
.status-chip {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
}

.status-pending       { background:#ffe5e5; color:#c0392b; border:1px solid #e74c3c; }
.status-hr-approved   { background:#fff3e0; color:#e67e22; border:1px solid #f39c12; }
.status-mgmt-approved { background:#e0f7fa; color:#00838f; border:1px solid #00acc1; }
.status-approved      { background:#e6f9ee; color:#27ae60; border:1px solid #2ecc71; }
.status-disapproved   { background:#f2f2f2; color:#7f8c8d; border:1px solid #bdc3c7; }

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
    color: #555;
    white-space: nowrap;
}

.leave-filter-bar input[type="date"] {
    padding: 5px 8px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
}

.leave-filter-bar input[type="date"]:focus {
    outline: none;
    border-color: #4a90d9;
    box-shadow: 0 0 0 2px rgba(74,144,217,.2);
}

.btn-filter {
    padding: 5px 14px;
    background: #4a90d9;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background .2s;
}
.btn-filter:hover { background: #2f78c5; }

.btn-filter-reset {
    padding: 5px 14px;
    background: #eee;
    color: #555;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background .2s;
}
.btn-filter-reset:hover { background: #ddd; }

.leave-loading {
    text-align: center;
    padding: 20px;
    color: #888;
    font-size: 0.9rem;
    display: none;
}
</style>

<div class="leave-card">

    <div class="leave-header">
        <h3>My Leave Applications</h3>

        <!-- ── AJAX date-range filter — no <form>, no page reload ── -->
        <div class="leave-filter-bar">
            <label for="lf_date_from">From</label>
            <input type="date" id="lf_date_from"
                   value="<?= htmlspecialchars($defaultFrom) ?>">

            <label for="lf_date_to">To</label>
            <input type="date" id="lf_date_to"
                   value="<?= htmlspecialchars($defaultTo) ?>">

            <button type="button" class="btn-filter"       onclick="leaveFilter()">Filter</button>
            <button type="button" class="btn-filter-reset" onclick="leaveFilterReset()">Reset</button>
        </div>

        <button class="btn-new-leave" onclick="openLeaveModal()">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            New Leave Application
        </button>
    </div>

    <div class="leave-loading" id="leaveLoading">Loading…</div>

    <div class="leave-table-wrap">
        <table class="leave-table">
            <thead>
                <tr>
                    <th>Actions</th>
                    <th>Date Filed</th>
                    <th>Particulars</th>
                    <th>No. of Days</th>
                    <th>Inclusive Dates</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody id="leaveTableBody">
            <?php while ($row = $resLeave->fetch_assoc()):
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
                <tr id="leave-row-<?= $row['app_ID'] ?>">
                    <td>
                        <div class="action-buttons">
                            <?php if ($isPending): ?>
                            <button class="icon-btn btn-edit"
                                    data-row="<?= $rowData ?>"
                                    onclick="lmEditClick(this)">✏️</button>
                            <button class="icon-btn btn-delete"
                                    onclick="deleteLeave(<?= (int)$row['app_ID'] ?>)">🗑️</button>
                            <?php else: ?>
                            <button class="icon-btn btn-edit"   disabled>✏️</button>
                            <button class="icon-btn btn-delete" disabled>🗑️</button>
                            <?php endif; ?>
                            <button class="icon-btn btn-print"
                                    onclick="printLeave(<?= (int)$row['app_ID'] ?>)"
                                    title="Print Leave Application">🖨️</button>
                        </div>
                    </td>
                    <td>
                        <?= date('M d, Y', strtotime($row['DOF'])) ?><br>
                        <small><?= htmlspecialchars($row['TOF']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($row['LeaveDescription']) ?></td>
                    <td><?= htmlspecialchars($row['NOD']) ?></td>
                    <td><?= htmlspecialchars($row['Inclusive_Dates']) ?></td>
                    <td>
                        <span class="status-chip <?= $statusClass ?>">
                            <?= htmlspecialchars($statusRaw) ?>
                        </span>
                    </td>
                    <td><?= !empty($row['Remarks']) ? htmlspecialchars($row['Remarks']) : '—' ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
/* ── Status → CSS class (mirrors PHP leaveStatusClass()) ─────────────────── */
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

/* ── Build a <tr> string from a JSON row returned by get_leaves.php ───────── */
function leaveBuildRow(row) {
    const isPending   = (row.Status || '').toLowerCase() === 'pending approval';
    const statusClass = leaveGetStatusClass(row.Status);

    // Safely encode the data-row JSON for an HTML attribute
    const rowDataObj = {
        app_ID:          row.app_ID,
        lt_ID:           row.lt_ID,
        dol_b:           row.DOL_B,
        dol_c:           row.DOL_C || '',
        nod:             row.NOD,
        inclusive_dates: row.Inclusive_Dates,
    };
    // Use a temporary element to safely set the attribute value
    const tmp = document.createElement('div');
    tmp.dataset.row = JSON.stringify(rowDataObj);
    const safeRowData = tmp.getAttribute('data-row')
                           .replace(/&/g,'&amp;')
                           .replace(/"/g,'&quot;');

    const editBtn = isPending
        ? `<button class="icon-btn btn-edit" data-row="${safeRowData}" onclick="lmEditClick(this)">✏️</button>`
        : `<button class="icon-btn btn-edit" disabled>✏️</button>`;

    const deleteBtn = isPending
        ? `<button class="icon-btn btn-delete" onclick="deleteLeave(${parseInt(row.app_ID)})">🗑️</button>`
        : `<button class="icon-btn btn-delete" disabled>🗑️</button>`;

    const remarks = row.Remarks
        ? row.Remarks.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        : '—';
    const desc = (row.LeaveDescription || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const status = (row.Status || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const tof  = (row.TOF || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const incl = (row.Inclusive_Dates || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    return `
        <tr id="leave-row-${parseInt(row.app_ID)}">
            <td>
                <div class="action-buttons">
                    ${editBtn}
                    ${deleteBtn}
                    <button class="icon-btn btn-print"
                            onclick="printLeave(${parseInt(row.app_ID)})"
                            title="Print Leave Application">🖨️</button>
                </div>
            </td>
            <td>${leaveFmtDate(row.DOF)}<br><small>${tof}</small></td>
            <td>${desc}</td>
            <td>${row.NOD || ''}</td>
            <td>${incl}</td>
            <td><span class="status-chip ${statusClass}">${status}</span></td>
            <td>${remarks}</td>
        </tr>`;
}

/* ── Core AJAX fetch ─────────────────────────────────────────────────────── */
async function leaveFetchRows(dateFrom, dateTo) {
    const loading = document.getElementById('leaveLoading');
    const tbody   = document.getElementById('leaveTableBody');

    loading.style.display = 'block';
    tbody.style.opacity   = '0.4';
    tbody.style.pointerEvents = 'none';

    try {
        const params = new URLSearchParams({ date_from: dateFrom, date_to: dateTo });
        const res    = await fetch('Modules/Leave/get_leaves.php?' + params);
        if (!res.ok) throw new Error('Network error ' + res.status);

        const data = await res.json();
        if (data.error) throw new Error(data.error);

        tbody.innerHTML = data.rows.length
            ? data.rows.map(leaveBuildRow).join('')
            : `<tr><td colspan="7" style="text-align:center;color:#888;padding:20px;">
                   No records found for this period.
               </td></tr>`;
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#c0392b;padding:20px;">
                               Error loading records. Please try again.
                           </td></tr>`;
        console.error('leaveFilter error:', err);
    } finally {
        loading.style.display     = 'none';
        tbody.style.opacity       = '1';
        tbody.style.pointerEvents = '';
    }
}

/* ── Filter button ───────────────────────────────────────────────────────── */
function leaveFilter() {
    const from = document.getElementById('lf_date_from').value;
    const to   = document.getElementById('lf_date_to').value;

    if (!from || !to) {
        alert('Please select both From and To dates.');
        return;
    }
    if (from > to) {
        alert('"From" date cannot be later than "To" date.');
        return;
    }

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