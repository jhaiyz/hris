<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hris');
define('PHOTOS_DIR', 'C:/xampp/htdocs/hris/photos/');
define('PHOTOS_URL', 'photos/');
//define('PHOTOS_URL', 'http://localhost:8080/hris/photos/');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

session_start();

function isLoggedIn() {
    return isset($_SESSION['emp_ID']) && !empty($_SESSION['emp_ID']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}
?>
