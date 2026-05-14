<?php
require_once '../db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data        = json_decode(file_get_contents('php://input'), true);
$newPassword = trim($data['new_password'] ?? '');
$currentPw   = trim($data['current_password'] ?? '');

if (empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'New password is required.']);
    exit;
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

if ($newPassword === '123456') {
    echo json_encode(['success' => false, 'message' => 'You cannot use the default password.']);
    exit;
}

$db = getDB();

// If current_password is provided (from portal Change Password panel), verify it
if (!empty($currentPw)) {
    $stmt = $db->prepare("SELECT Password FROM tblemp WHERE emp_ID = ? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['emp_ID']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || $row['Password'] !== $currentPw) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        $db->close();
        exit;
    }
}

$stmt = $db->prepare("UPDATE tblemp SET Password = ? WHERE emp_ID = ?");
$stmt->bind_param('si', $newPassword, $_SESSION['emp_ID']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$db->close();
?>