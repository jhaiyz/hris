<?php
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true);
$nick_name = trim($input['nick_name'] ?? '');
$password  = trim($input['password'] ?? '');

if (empty($nick_name) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please provide your nickname and password.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT emp_ID, Full_Name, Nick_Name, Password, imgPath FROM tblemp WHERE Nick_Name = ? LIMIT 1");
$stmt->bind_param('s', $nick_name);
$stmt->execute();
$result = $stmt->get_result();
$emp    = $result->fetch_assoc();

if (!$emp) {
    echo json_encode(['success' => false, 'message' => 'Employee not found. Check your nickname.']);
    exit;
}

if ($emp['Password'] !== $password) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password. Please try again.']);
    exit;
}

// Successful login
$_SESSION['emp_ID']    = $emp['emp_ID'];
$_SESSION['Full_Name'] = $emp['Full_Name'];
$_SESSION['imgPath']   = $emp['imgPath'];

$mustChange = ($emp['Password'] === '123456');

echo json_encode([
    'success'            => true,
    'mustChangePassword' => $mustChange,
]);

$stmt->close();
$db->close();
?>