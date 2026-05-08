<?php
require_once "config.php";

if (!isset($_GET['id'])) {
    die("No file specified");
}

$id = intval($_GET['id']);

// Prepare query (only file_path and file_name)
$stmt = $conn->prepare("SELECT file_path, file_name FROM tblfiles WHERE file_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("File not found");
}

$file = $result->fetch_assoc();
$filePath = __DIR__ . "/storage/" . $file['file_path'];
$fileName = $file['file_name'];

if (!file_exists($filePath)) {
    die("File does not exist on server");
}

// Determine MIME type dynamically
$mimeType = mime_content_type($filePath);

// Send headers for inline preview
header("Content-Type: $mimeType");
header("Content-Disposition: inline; filename=\"" . basename($fileName) . "\"");
header("Content-Length: " . filesize($filePath));

readfile($filePath);
exit;
?>