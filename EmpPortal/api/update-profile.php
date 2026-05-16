<?php
require_once '../db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

// Sanitize — trim and uppercase
function sup($val) {
    return isset($val) && $val !== '' ? strtoupper(trim($val)) : null;
}

// Date helper — keeps null if empty, otherwise returns as-is (already YYYY-MM-DD from the frontend)
function supDate($val) {
    return isset($val) && $val !== '' ? trim($val) : null;
}

$nickName    = sup($data['Nick_Name']);
$firstName   = sup($data['First_Name']);
$middleName  = sup($data['Middle_Name']);
$lastName    = sup($data['Last_Name']);
$extName     = sup($data['Ext_Name']);
$birthday    = supDate($data['Birthday']);
$office      = sup($data['Office']);
$empStatus   = sup($data['Employment_Status']);
$position    = sup($data['Position']);
$mobileNo    = sup($data['Mobile_No']);
$email       = sup($data['Email']);
$cpEmergency = sup($data['CP_Emergency']);
$prcNo       = sup($data['PRC_No']);
$prcExpDate  = supDate($data['PRC_ExpDate']);
$s2No        = sup($data['S2_No']);
$s2ExpDate   = supDate($data['S2_ExpDate']);
$phAccred    = sup($data['PH_Accred']);
$profSuffixes = sup($data['Prof_Suffixes']);

// If the license number is cleared, also clear its expiration date
if (empty($prcNo)) $prcExpDate = null;
if (empty($s2No))  $s2ExpDate  = null;

// Required check
$required = [
    'Nickname'          => $nickName,
    'First Name'        => $firstName,
    'Last Name'         => $lastName,
    'Birthday'          => $birthday,
    'Office'            => $office,
    'Employment Status' => $empStatus,
    'Position'          => $position,
    'Email'             => $email,
];
foreach ($required as $label => $val) {
    if (empty($val)) {
        echo json_encode(['success' => false, 'message' => "$label is required."]);
        exit;
    }
}

// If PRC No. is provided, its expiration date is also required
if (!empty($prcNo) && empty($prcExpDate)) {
    echo json_encode(['success' => false, 'message' => 'PRC Expiration Date is required when PRC No. is provided.']);
    exit;
}

// If S2 No. is provided, its expiration date is also required
if (!empty($s2No) && empty($s2ExpDate)) {
    echo json_encode(['success' => false, 'message' => 'S2 Expiration Date is required when S2 No. is provided.']);
    exit;
}

// Build Full_Name
$fullName = $lastName . ', ' . $firstName;
if ($middleName) $fullName .= ' ' . $middleName;
if ($extName)    $fullName .= ' ' . $extName;

$db    = getDB();
$empID = $_SESSION['emp_ID'];

// Check duplicate nickname/email/mobile/prc/s2 (excluding current user)
function checkDupExclude($db, $field, $value, $label, $excludeId) {
    if (empty($value)) return null;
    $stmt = $db->prepare("SELECT emp_ID FROM tblemp WHERE `$field` = ? AND emp_ID != ? LIMIT 1");
    $stmt->bind_param('si', $value, $excludeId);
    $stmt->execute();
    $stmt->store_result();
    $found = $stmt->num_rows > 0;
    $stmt->close();
    return $found ? $label : null;
}

$dups = array_filter([
    checkDupExclude($db, 'Nick_Name', $nickName, 'Nickname',   $empID),
    checkDupExclude($db, 'Email',     $email,    'Email',      $empID),
    !empty($mobileNo) ? checkDupExclude($db, 'Mobile_No', $mobileNo, 'Mobile No.', $empID) : null,
    !empty($prcNo)    ? checkDupExclude($db, 'PRC_No',    $prcNo,    'PRC No.',    $empID) : null,
    !empty($s2No)     ? checkDupExclude($db, 'S2_No',     $s2No,     'S2 No.',     $empID) : null,
]);

if (!empty($dups)) {
    echo json_encode(['success' => false, 'message' => 'The following are already in use: ' . implode(', ', $dups)]);
    $db->close();
    exit;
}

$stmt = $db->prepare("
    UPDATE tblemp SET
        Nick_Name=?, First_Name=?, Middle_Name=?, Last_Name=?, Ext_Name=?,
        Full_Name=?, Birthday=?, Office=?, Employment_Status=?, Position=?,
        Mobile_No=?, Email=?, CP_Emergency=?,
        PRC_No=?, PRC_ExpDate=?, S2_No=?, S2_ExpDate=?,
        PH_Accred=?, Prof_Suffixes=?
    WHERE emp_ID=?
");
$stmt->bind_param(
    'sssssssssssssssssssi',
    $nickName, $firstName, $middleName, $lastName, $extName,
    $fullName, $birthday, $office, $empStatus, $position,
    $mobileNo, $email, $cpEmergency,
    $prcNo, $prcExpDate, $s2No, $s2ExpDate,
    $phAccred, $profSuffixes,
    $empID
);

if ($stmt->execute()) {
    $_SESSION['Full_Name'] = $fullName;
    echo json_encode(['success' => true, 'full_name' => $fullName]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$db->close();
?>