<?php
header('Content-Type: application/json');
include 'config.php';

$emp = trim($_GET['employee_no'] ?? '');
if($emp===''){
    echo json_encode(['success'=>false,'message'=>'Employee number missing']);
    exit;
}

// Get current month start and end
$start = date('Y-m-01');
$end = date('Y-m-t');

$stmt = $conn->prepare("SELECT Ref_Date, Ref_Time, Log_Type, Area FROM tbllogs WHERE Employee_No=? AND Ref_Date BETWEEN ? AND ? ORDER BY Ref_Date ASC, Ref_Time ASC");
$stmt->bind_param("sss",$emp,$start,$end);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while($row = $result->fetch_assoc()){
    $logs[] = $row;
}

echo json_encode(['success'=>true,'logs'=>$logs]);
exit;