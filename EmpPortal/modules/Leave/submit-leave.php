<?php

require_once '../../db.php';

header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);

    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$lt_ID           = intval($input['lt_ID'] ?? 0);
$dol_a           = trim($input['dol_a'] ?? '');
$dol_b           = trim($input['dol_b'] ?? '');
$dol_c           = trim($input['dol_c'] ?? '');
$nod             = floatval($input['nod'] ?? 0);
$inclusive_dates = trim($input['inclusive_dates'] ?? '');

$emp_ID = $_SESSION['emp_ID'];

// Validation
if (!$lt_ID) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid leave type.'
    ]);

    exit;
}

if (empty($dol_a) || empty($dol_b)) {

    echo json_encode([
        'success' => false,
        'message' => 'Leave details required.'
    ]);

    exit;
}

if ($nod <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid number of days.'
    ]);

    exit;
}

if (empty($inclusive_dates)) {

    echo json_encode([
        'success' => false,
        'message' => 'Inclusive dates required.'
    ]);

    exit;
}

$db = getDB();

$db->begin_transaction();

try {

    $stmt = $db->prepare("
        INSERT INTO tblappleave
        (
            emp_ID,
            lt_ID,
            DOF,
            TOF,
            DOL_A,
            DOL_B,
            DOL_C,
            NOD,
            Inclusive_Dates,
            Status
        )
        VALUES
        (
            ?,
            ?,
            CURDATE(),
            CURTIME(),
            ?,
            ?,
            ?,
            ?,
            ?,
            'Pending Approval'
        )
    ");

    $stmt->bind_param(
        'iisssds',
        $emp_ID,
        $lt_ID,
        $dol_a,
        $dol_b,
        $dol_c,
        $nod,
        $inclusive_dates
    );

    $stmt->execute();

    $app_ID = $db->insert_id;

    $stmt->close();

    // Notification
    $stmt2 = $db->prepare("
        INSERT INTO tblnotification
        (
            Transaction_Type,
            Transaction_ID,
            Sender_ID,
            Title,
            Status
        )
        VALUES
        (
            'Leave',
            ?,
            ?,
            'Leave Application',
            'Pending Approval'
        )
    ");

    $stmt2->bind_param('ii', $app_ID, $emp_ID);

    $stmt2->execute();

    $stmt2->close();

    $db->commit();

    echo json_encode([
        'success' => true,
        'app_ID' => $app_ID
    ]);

} catch (Exception $e) {

    $db->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {

    $db->close();

}