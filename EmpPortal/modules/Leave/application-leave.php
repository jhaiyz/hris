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
?>

<div class="leave-card">

    <div class="leave-header">
        <h3>My Leave Applications</h3>
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

            <tbody>

            <?php if ($resLeave && $resLeave->num_rows > 0): ?>

                <?php while ($row = $resLeave->fetch_assoc()): ?>

                    <?php
                        $isPending = ($row['Status'] === 'Pending Approval');

                        $statusClass = 'status-pending';
                        if ($row['Status'] === 'Approved') {
                            $statusClass = 'status-approved';
                        } elseif ($row['Status'] === 'Disapproved') {
                            $statusClass = 'status-disapproved';
                        }
                    ?>

                    <tr>

                        <td>
                            <div class="action-buttons">

                                <button class="icon-btn btn-edit" <?= !$isPending ? 'disabled' : '' ?>>
                                    ✏️
                                </button>

                                <button class="icon-btn btn-delete" <?= !$isPending ? 'disabled' : '' ?>>
                                    🗑️
                                </button>

                                <button class="icon-btn btn-print">
                                    🖨️
                                </button>

                            </div>
                        </td>

                        <td>
                            <?= date('M d, Y', strtotime($row['DOF'])) ?>
                            <br>
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
                            <?= !empty($row['Remarks']) 
                                ? htmlspecialchars($row['Remarks']) 
                                : '—' ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="7" style="text-align:center; padding:20px;">
                        No leave applications found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php
$stmtLeave->close();
$dbLeave->close();
?>