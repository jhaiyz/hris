<?php
require_once '../db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("
    SELECT Employee_No, Nick_Name, First_Name, Middle_Name, Last_Name, Ext_Name,
           Full_Name, Birthday, Office, Mobile_No, Email, Employment_Status,
           Position, PRC_No, PH_Accred, CP_Emergency
    FROM tblemp
    WHERE emp_ID = ? LIMIT 1
");
$stmt->bind_param('i', $_SESSION['emp_ID']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();
$db->close();

if (!$profile) {
    echo json_encode(['success' => false, 'message' => 'Employee not found.']);
    exit;
}

// Format birthday for date input (YYYY-MM-DD)
if (!empty($profile['Birthday'])) {
    $profile['Birthday'] = date('Y-m-d', strtotime($profile['Birthday']));
}

echo json_encode(['success' => true, 'profile' => $profile]);
?>