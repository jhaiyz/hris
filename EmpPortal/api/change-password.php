<?php
require_once '../db.php';
header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$new_pw = trim($input['new_password'] ?? '');

if (empty($new_pw)) {
    echo json_encode(['success' => false, 'message' => 'Password cannot be empty.']);
    exit;
}

if (strlen($new_pw) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

if ($new_pw === '123456') {
    echo json_encode(['success' => false, 'message' => 'Cannot reuse the default password.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("UPDATE tblemp SET Password = ? WHERE emp_ID = ?");
$stmt->bind_param('si', $new_pw, $_SESSION['emp_ID']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}

$stmt->close();
$db->close();
?>
