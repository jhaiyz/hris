<?php

$target_dir = "C:/xampp/htdocs/hris/photos/";
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

// Headers
$origName    = $_SERVER['HTTP_X_FILENAME'] ?? 'file.bin';
$chunkNumber = intval($_SERVER['HTTP_X_CHUNKNUMBER'] ?? 1);
$totalChunks = intval($_SERVER['HTTP_X_TOTALCHUNKS'] ?? 1);
$uploadId    = $_SERVER['HTTP_X_UPLOADID'] ?? uniqid();

// Sanitize filename
$origName = preg_replace('/[^A-Za-z0-9._-]/', '', $origName);

// Unique temp file reference
$tmpFile = $target_dir . $uploadId . "_" . $origName . ".tmpname";

// Generate unique physical filename
if ($chunkNumber == 1) {
    $uniqueName = time() . "_" . bin2hex(random_bytes(4)) . "_" . $origName;
    file_put_contents($tmpFile, $uniqueName);
} else {
    $uniqueName = file_get_contents($tmpFile);
}

$filepath = $target_dir . $uniqueName;
$mode = ($chunkNumber == 1) ? "wb" : "ab";

// Write chunk
$input  = fopen("php://input", "rb");
$output = fopen($filepath, $mode);
stream_copy_to_stream($input, $output);
fclose($input);
fclose($output);

// Last chunk cleanup
if ($chunkNumber == $totalChunks) {
    unlink($tmpFile);

    if (!file_exists($filepath)) {
        http_response_code(500);
        exit("Upload failed");
    }
}

echo json_encode([
    'status' => 'ok',
    'chunk' => $chunkNumber,
    'total_chunks' => $totalChunks,
    'file_path' => $uniqueName
]);

?>