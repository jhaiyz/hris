<?php
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

// Sanitize helper — trims and converts to uppercase
function s($val) {
    return isset($val) && $val !== '' ? strtoupper(trim($val)) : null;
}

$empNo       = s($data['Employee_No']);
$nickName    = s($data['Nick_Name']);
$firstName   = s($data['First_Name']);
$middleName  = s($data['Middle_Name']);
$lastName    = s($data['Last_Name']);
$extName     = s($data['Ext_Name']);
$fullName    = s($data['Full_Name']);
$birthday    = s($data['Birthday']);
$office      = s($data['Office']);
$mobileNo    = s($data['Mobile_No']);
$email       = s($data['Email']);
$empStatus   = s($data['Employment_Status']);
$position    = s($data['Position']);
$prcNo       = s($data['PRC_No']);
$phAccred    = s($data['PH_Accred']);
$cpEmergency = s($data['CP_Emergency']);

// Server-side required check
$required = [
    'Employee No.'        => $empNo,
    'Nickname'            => $nickName,
    'First Name'          => $firstName,
    'Last Name'           => $lastName,
    'Full Name'           => $fullName,
    'Birthday'            => $birthday,
    'Office'              => $office,
    'Email'               => $email,
    'Employment Status'   => $empStatus,
    'Position'            => $position,
];
foreach ($required as $label => $val) {
    if (empty($val)) {
        echo json_encode(['success' => false, 'message' => "$label is required."]);
        exit;
    }
}

$db = getDB();

// ─── Duplicate checks ───────────────────────────────────────────────────────
$duplicates = [];

// Helper: check unique field
function checkDuplicate($db, $field, $value, $label, &$duplicates) {
    if (empty($value)) return;
    $stmt = $db->prepare("SELECT emp_ID FROM tblemp WHERE `$field` = ? LIMIT 1");
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $duplicates[] = $label;
    }
    $stmt->close();
}

checkDuplicate($db, 'Employee_No', $empNo,    'Employee No.',             $duplicates);
checkDuplicate($db, 'Full_Name',   $fullName, 'Full Name',                $duplicates);
checkDuplicate($db, 'Nick_Name',   $nickName, 'Nickname',                 $duplicates);
checkDuplicate($db, 'Email',       $email,    'Email Address',            $duplicates);

if (!empty($mobileNo)) {
    checkDuplicate($db, 'Mobile_No', $mobileNo, 'Mobile No.', $duplicates);
}
if (!empty($prcNo)) {
    checkDuplicate($db, 'PRC_No',    $prcNo,    'PRC No.',    $duplicates);
}
if (!empty($phAccred)) {
    checkDuplicate($db, 'PH_Accred', $phAccred, 'PhilHealth Accreditation', $duplicates);
}

if (!empty($duplicates)) {
    $list = implode(', ', $duplicates);
    echo json_encode([
        'success' => false,
        'message' => "The following field(s) are already registered: $list. Please verify your information."
    ]);
    $db->close();
    exit;
}

// ─── Insert ─────────────────────────────────────────────────────────────────
$stmt = $db->prepare("
    INSERT INTO tblemp
        (Employee_No, Nick_Name, First_Name, Middle_Name, Last_Name, Ext_Name,
         Full_Name, Birthday, Office, Mobile_No, Email, Employment_Status,
         Position, Status, Password, PRC_No, PH_Accred, CP_Emergency)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', '123456', ?, ?, ?)
");

$stmt->bind_param(
    'ssssssssssssssss',
    $empNo, $nickName, $firstName, $middleName, $lastName, $extName,
    $fullName, $birthday, $office, $mobileNo, $email, $empStatus,
    $position, $prcNo, $phAccred, $cpEmergency
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$db->close();