<?php
header('Content-Type: application/json');
include 'config.php';

$employeeNo = $_GET['employee_no'] ?? '';
if(!$employeeNo){
    echo json_encode(["success"=>false,"message"=>"Employee number missing"]);
    exit;
}

// Fetch devices
$stmt = $conn->prepare("SELECT Credential_ID FROM tbldevices WHERE Employee_No=?");
$stmt->bind_param("s",$employeeNo);
$stmt->execute();
$result = $stmt->get_result();

$allowCredentials = [];
while($row=$result->fetch_assoc()){
    if(!empty($row['Credential_ID'])){
        $allowCredentials[]=[
            "type"=>"public-key",
            "id"=>$row['Credential_ID']
        ];
    }
}

// Challenge
$challenge=random_bytes(32);
$_SESSION['challenge']=base64_encode($challenge);

echo json_encode([
    "success"=>true,
    "challenge"=>base64_encode($challenge),
    "timeout"=>60000,
    "userVerification"=>"preferred",
    "allowCredentials"=>$allowCredentials
]);