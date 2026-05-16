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
           Position, PRC_No, PRC_ExpDate, S2_No, S2_ExpDate,
           PH_Accred, Prof_Suffixes, CP_Emergency
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

// Format date fields for HTML date inputs (YYYY-MM-DD)
foreach (['Birthday', 'PRC_ExpDate', 'S2_ExpDate'] as $dateField) {
    if (!empty($profile[$dateField])) {
        $profile[$dateField] = date('Y-m-d', strtotime($profile[$dateField]));
    }
}

echo json_encode(['success' => true, 'profile' => $profile]);
?>