<?php
header('Content-Type: application/json');
include 'config.php';

$employeeNo = $_GET['employee_no'] ?? '';
if(!$employeeNo){
    echo json_encode(['exists'=>false]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tbldevices WHERE Employee_No=? LIMIT 1");
$stmt->bind_param("s",$employeeNo);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['exists'=>$result->num_rows>0]);