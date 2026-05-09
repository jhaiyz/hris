<?php

require_once '../../db.php';

header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$app_ID = intval($input['app_ID'] ?? 0);
$emp_ID = $_SESSION['emp_ID'];

if (!$app_ID) {
    echo json_encode(['success' => false, 'message' => 'Invalid application ID.']);
    exit;
}

$db = getDB();
$db->begin_transaction();

try {

    // Re-check status and ownership atomically
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
            'message' => 'This application can no longer be deleted (status: ' . $currentStatus . ').'
        ]);
        exit;
    }

    // Delete notification records first (FK safety)
    $del1 = $db->prepare("
        DELETE FROM tblnotification
        WHERE Transaction_Type = 'Leave' AND Transaction_ID = ?
    ");
    $del1->bind_param('i', $app_ID);
    $del1->execute();
    $del1->close();

    // Delete the leave application
    $del2 = $db->prepare("
        DELETE FROM tblappleave
        WHERE app_ID = ? AND emp_ID = ?
    ");
    $del2->bind_param('ii', $app_ID, $emp_ID);
    $del2->execute();
    $del2->close();

    $db->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);

} finally {

    $db->close();

}