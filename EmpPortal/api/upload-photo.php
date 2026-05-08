<?php
require_once '../db.php';
header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $errMsg = 'No file uploaded or upload error.';
    if (isset($_FILES['photo'])) {
        $codes = [
            1 => 'File exceeds server limit.',
            2 => 'File exceeds form limit.',
            3 => 'File partially uploaded.',
            4 => 'No file selected.',
        ];
        $errMsg = $codes[$_FILES['photo']['error']] ?? 'Upload error code: '.$_FILES['photo']['error'];
    }
    echo json_encode(['success' => false, 'message' => $errMsg]);
    exit;
}

$file     = $_FILES['photo'];
$maxBytes = 1.5 * 1024 * 1024; // 1.5 MB

// Validate file size
if ($file['size'] > $maxBytes) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 1.5 MB limit.']);
    exit;
}

// Validate MIME type
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedMimes)) {
    echo json_encode(['success' => false, 'message' => 'Only JPEG, PNG, GIF, or WEBP images are allowed.']);
    exit;
}

$extMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$ext      = $extMap[$mime];
$emp_id   = $_SESSION['emp_ID'];
$newFile  = $emp_id . '.' . $ext;
$photosDir = PHOTOS_DIR;

// Ensure photos directory exists
if (!is_dir($photosDir)) {
    if (!mkdir($photosDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Cannot create photos directory.']);
        exit;
    }
}

// Get current imgPath
$db   = getDB();
$stmt = $db->prepare("SELECT imgPath FROM tblemp WHERE emp_ID = ?");
$stmt->bind_param('i', $emp_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$currentImg = $row['imgPath'] ?? null;

// Step b: delete old file if imgPath is not null
if (!empty($currentImg)) {
    $oldFile = $photosDir . $currentImg;
    if (file_exists($oldFile)) {
        unlink($oldFile);
    }
}

// Step a: save new file with emp_ID as filename
$destPath = $photosDir . $newFile;
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save the uploaded file.']);
    $db->close();
    exit;
}

// Update imgPath in database
$stmt = $db->prepare("UPDATE tblemp SET imgPath = ? WHERE emp_ID = ?");
$stmt->bind_param('si', $newFile, $emp_id);

if ($stmt->execute()) {
    $_SESSION['imgPath'] = $newFile;
    echo json_encode([
        'success'  => true,
        'imgPath'  => $newFile,
        'imgUrl'   => PHOTOS_URL . $newFile,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}

$stmt->close();
$db->close();
?>
