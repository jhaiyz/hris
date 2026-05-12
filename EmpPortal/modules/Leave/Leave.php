<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

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
    LEFT JOIN tbl_lt l
        ON a.lt_ID = l.lt_ID
    WHERE a.emp_ID = ?
    ORDER BY a.DOF DESC, a.TOF DESC
";

$stmtLeave = $dbLeave->prepare($sqlLeave);
$stmtLeave->bind_param('i', $_SESSION['emp_ID']);
$stmtLeave->execute();
$resLeave = $stmtLeave->get_result();

// Fetch leave types for the dropdown
$dbLT = getDB();
$resLT = $dbLT->query("SELECT lt_ID, Description FROM tbl_lt WHERE Type = 'Leave' ORDER BY Description");
$leaveTypes = [];
while ($lt = $resLT->fetch_assoc()) {
    $leaveTypes[] = $lt;
}
$dbLT->close();
?>

<style>
/* ── Leave table: fixed layout so headers always align with their cells ── */
.leave-table {
    width: 100%;
    table-layout: fixed;       /* key: columns honour the widths below */
    border-collapse: collapse;
}

/* Per-column widths  (must total 100 %) */
.leave-table th:nth-child(1),
.leave-table td:nth-child(1) { width: 10%; }   /* Actions        */

.leave-table th:nth-child(2),
.leave-table td:nth-child(2) { width: 13%; }   /* Date Filed     */

.leave-table th:nth-child(3),
.leave-table td:nth-child(3) { width: 20%; }   /* Particulars    */

.leave-table th:nth-child(4),
.leave-table td:nth-child(4) { width: 9%;  }   /* No. of Days    */

.leave-table th:nth-child(5),
.leave-table td:nth-child(5) { width: 18%; }   /* Inclusive Dates*/

.leave-table th:nth-child(6),
.leave-table td:nth-child(6) { width: 13%; }   /* Status         */

.leave-table th:nth-child(7),
.leave-table td:nth-child(7) { width: 17%; }   /* Remarks        */

/* Consistent alignment */
.leave-table thead th {
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}

.leave-table tbody td {
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;      /* long text wraps instead of blowing out */
    overflow-wrap: break-word;
}

/* Left-align text-heavy columns */
.leave-table tbody td:nth-child(3),   /* Particulars  */
.leave-table tbody td:nth-child(7) {  /* Remarks      */
    text-align: left;
}

/* Keep action buttons centred and on one line */
.leave-table .action-buttons {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 4px;
    flex-wrap: nowrap;
}
</style>

<div class="leave-card">

    <div class="leave-header">
        <h3>My Leave Applications</h3>
        <button class="btn-new-leave" onclick="openLeaveModal()">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            New Leave Application
        </button>
    </div>

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
            <?php while ($row = $resLeave->fetch_assoc()): ?>
                <?php
                    $isPending  = ($row['Status'] === 'Pending Approval');
                    $statusClass = 'status-pending';
                    if ($row['Status'] === 'Approved')    $statusClass = 'status-approved';
                    elseif ($row['Status'] === 'Disapproved') $statusClass = 'status-disapproved';

                    // JSON stored in data-row attribute — no quote collision with onclick
                    $rowData = htmlspecialchars(json_encode([
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
                            title="Print Leave Application">🖨️
                            </button>
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
                            <?= htmlspecialchars($row['Status']) ?>
                        </span>
                    </td>
                    <td>
                        <?= !empty($row['Remarks']) ? htmlspecialchars($row['Remarks']) : '—' ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>
</div>

<?php
$stmtLeave->close();
$dbLeave->close();
?>