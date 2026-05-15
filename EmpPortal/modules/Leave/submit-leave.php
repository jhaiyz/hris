<?php

require_once '../../db.php';

header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$app_ID          = isset($input['app_ID']) && $input['app_ID'] ? intval($input['app_ID']) : null;
$lt_ID           = intval($input['lt_ID']           ?? 0);
$dol_a           = trim($input['dol_a']             ?? '');
$dol_b           = trim($input['dol_b']             ?? '');
$dol_c           = trim($input['dol_c']             ?? '');
$nod             = floatval($input['nod']           ?? 0);
$inclusive_dates = trim($input['inclusive_dates']   ?? '');
$emp_ID          = $_SESSION['emp_ID'];
$leave_date = trim($input['leave_date'] ?? '');

// ── Shared validation ──────────────────────────────────────────
if (!$lt_ID) {
    echo json_encode(['success' => false, 'message' => 'Invalid leave type.']);
    exit;
}

if (empty($dol_a) || empty($dol_b)) {
    echo json_encode(['success' => false, 'message' => 'Leave details required.']);
    exit;
}

if ($nod <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid number of days.']);
    exit;
}

if (empty($inclusive_dates)) {
    echo json_encode(['success' => false, 'message' => 'Inclusive dates required.']);
    exit;
}

$db = getDB();
$db->begin_transaction();

try {

    if ($app_ID) {

        // ── UPDATE path ────────────────────────────────────────
        // Re-check status and ownership before touching anything
        $chk = $db->prepare("
            SELECT Status FROM tblappleave
            WHERE app_ID = ? AND emp_ID = ?
            FOR UPDATE
        ");
        $chk->bind_param('ii', $app_ID, $emp_ID);
        $chk->execute();
        $chk->bind_result($currentStatus);

        if (!$chk->fetch()) {
            $chk->close();
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Application not found.']);
            exit;
        }
        $chk->close();

        if ($currentStatus !== 'Pending Approval') {
            $db->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'This application can no longer be modified (status: ' . $currentStatus . ').'
            ]);
            exit;
        }

        $stmt = $db->prepare("
            UPDATE tblappleave
            SET
                lt_ID           = ?,
                DOL_A           = ?,
                DOL_B           = ?,
                DOL_C           = ?,
                NOD             = ?,
                Inclusive_Dates = ?,
                Leave_Date       = ?
            WHERE app_ID = ? AND emp_ID = ?
        ");
        $stmt->bind_param('isssdssii', $lt_ID, $dol_a, $dol_b, $dol_c, $nod, $inclusive_dates, $leave_date, $app_ID, $emp_ID);
        $stmt->execute();
        $stmt->close();

        $db->commit();

        // Fetch the updated row to return to the client
        $row = lmFetchRow($db, $app_ID);

        echo json_encode(['success' => true, 'row' => $row]);

    } else {

        // ── INSERT path ────────────────────────────────────────
        $stmt = $db->prepare("
            INSERT INTO tblappleave
            (emp_ID, lt_ID, DOF, TOF, DOL_A, DOL_B, DOL_C, NOD, Inclusive_Dates, Leave_Date, Status)
            VALUES (?, ?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, 'Pending Approval')
        ");
        $stmt->bind_param('iisssdss', $emp_ID, $lt_ID, $dol_a, $dol_b, $dol_c, $nod, $inclusive_dates, $leave_date);
        $stmt->execute();

        $new_app_ID = $db->insert_id;
        $stmt->close();

        // Notification
        $stmt2 = $db->prepare("
            INSERT INTO tblnotification
            (Transaction_Type, Transaction_ID, Sender_ID, Title, Status)
            VALUES ('Leave', ?, ?, 'Leave Application', 'Pending Approval')
        ");
        $stmt2->bind_param('ii', $new_app_ID, $emp_ID);
        $stmt2->execute();
        $stmt2->close();

        $db->commit();

        // Fetch the new row to return to the client
        $row = lmFetchRow($db, $new_app_ID);

        echo json_encode(['success' => true, 'row' => $row]);

    }

} catch (Exception $e) {

    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);

} finally {

    $db->close();

}

// ── Helper: fetch one row with all display fields ──────────────
function lmFetchRow($db, $app_ID) {

    $s = $db->prepare("
        SELECT
            a.app_ID,
            DATE_FORMAT(a.DOF, '%b %d, %Y') AS dof_formatted,
            a.TOF                            AS tof,
            a.NOD                            AS nod,
            a.Inclusive_Dates                AS inclusive_dates,
            a.Leave_Date                     AS leave_date,
            a.Status                         AS status,
            COALESCE(a.Remarks, '')          AS remarks,
            a.lt_ID,
            a.DOL_B                          AS dol_b,
            COALESCE(a.DOL_C, '')            AS dol_c,
            l.Description                    AS leave_description
        FROM tblappleave a
        LEFT JOIN tbl_lt l ON a.lt_ID = l.lt_ID
        WHERE a.app_ID = ?
    ");
    $s->bind_param('i', $app_ID);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $s->close();

    if (!$row) return null;

    // Attach the edit_data blob JS needs for openLeaveModal()
    $row['edit_data'] = [
        'app_ID'          => (int) $row['app_ID'],
        'lt_ID'           => (int) $row['lt_ID'],
        'dol_b'           => $row['dol_b'],
        'dol_c'           => $row['dol_c'],
        'nod'             => $row['nod'],
        'inclusive_dates' => $row['inclusive_dates'],
        'leave_date'      => $row['leave_date'],
    ];

    return $row;

}