<?php
// verify-complete.php — enforce token check with correct table
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
header('Content-Type: application/json');
ob_start();

include 'config.php';

// Read POST JSON
$input = file_get_contents("php://input");
$data = json_decode($input,true);
if($data === null){
    ob_clean();
    echo json_encode(['success'=>false,'message'=>'Invalid JSON']);
    exit;
}

$emp     = trim($data['employeeNo'] ?? '');
$logType = trim($data['logType'] ?? '');
$area    = trim($data['area'] ?? 'Manual');
$token   = trim($data['token'] ?? '');

// Validate required fields
if($emp===''){
    ob_clean();
    echo json_encode(['success'=>false,'message'=>'Employee number missing']);
    exit;
}
if($logType===''){
    ob_clean();
    echo json_encode(['success'=>false,'message'=>'Log type missing']);
    exit;
}

// ---------------------------
// Step 1: Always validate QR token in tbl_qr_token
// ---------------------------
if($token === ''){
    ob_clean();
    echo json_encode(['success'=>false,'message'=>'QR token missing']);
    exit;
}

$stmt = $conn->prepare("SELECT Token FROM tbl_qr_token WHERE Token=? LIMIT 1");
if(!$stmt){
    ob_clean();
    echo json_encode(['success'=>false,'message'=>"Prepare failed: ".$conn->error]);
    exit;
}
$stmt->bind_param("s",$token);
if(!$stmt->execute()){
    ob_clean();
    echo json_encode(['success'=>false,'message'=>"Execute failed: ".$stmt->error]);
    exit;
}
$result = $stmt->get_result();
if($result->num_rows === 0){
    ob_clean();
    echo json_encode(['success'=>false,'message'=>'QR token expired']);
    exit;
}

// ---------------------------
// Step 2: Check employee Qr_Att for break/resume logs
// ---------------------------
$qrCheckTypes = ['DS-BREAK','DS-RESUME','NS-BREAK','NS-RESUME'];
if(in_array($logType, $qrCheckTypes)){
    $stmt = $conn->prepare("SELECT Qr_Att FROM tblemp WHERE Employee_No=? LIMIT 1");
    if(!$stmt){
        ob_clean();
        echo json_encode(['success'=>false,'message'=>"Prepare failed (Qr_Att check): ".$conn->error]);
        exit;
    }
    $stmt->bind_param("s",$emp);
    if(!$stmt->execute()){
        ob_clean();
        echo json_encode(['success'=>false,'message'=>"Execute failed (Qr_Att check): ".$stmt->error]);
        exit;
    }
    $row = $stmt->get_result()->fetch_assoc();
    if(!$row || intval($row['Qr_Att']) !== 1){
        ob_clean();
        echo json_encode(['success'=>false,'message'=>'You are not allowed to use QR for this log type']);
        exit;
    }
}

// ---------------------------
// Step 3: Save attendance log
// ---------------------------
$refDate = date("Y-m-d");
$refTime = date("H:i:s");

$stmt = $conn->prepare("INSERT INTO tbllogs (Employee_No, Ref_Date, Ref_Time, Log_Type, Area, Updated_By) VALUES (?,?,?,?,?,?)");
if(!$stmt){
    ob_clean();
    echo json_encode(['success'=>false,'message'=>'Prepare failed (Insert): '.$conn->error]);
    exit;
}

$updatedBy = 'biometric';
$stmt->bind_param("ssssss",$emp,$refDate,$refTime,$logType,$area,$updatedBy);
if(!$stmt->execute()){
    ob_clean();
    echo json_encode(['success'=>false,'message'=>'Execute failed (Insert): '.$stmt->error]);
    exit;
}

// Success
ob_clean();
echo json_encode(['success'=>true,'message'=>"Attendance '$logType' saved successfully"]);
exit;