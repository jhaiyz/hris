<?php
session_start();
header('Content-Type: application/json');
include 'config.php';

$employeeNo = $_GET['employee_no'] ?? '';
if(!$employeeNo) exit;

$challenge = base64_encode(random_bytes(32));
$_SESSION['challenge'] = $challenge;

echo json_encode(['challenge'=>$challenge]);