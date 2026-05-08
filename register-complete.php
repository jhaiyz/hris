<?php
header('Content-Type: application/json');
include 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$employeeNo = $data['employeeNo'];
$credId = $data['credId'];
$pubKey = $data['pubKey'];

$stmt = $conn->prepare("INSERT INTO tbldevices (Employee_No, Credential_ID, Public_Key) VALUES (?,?,?)");
$stmt->bind_param("sss",$employeeNo,$credId,$pubKey);
$stmt->execute();

echo json_encode(['status'=>'ok']);