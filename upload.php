<?php
$target_dir = "C:/xampp/htdocs/hris/photos/";
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

// read headers from VB6
$filename     = $_SERVER['HTTP_X_FILENAME'] ?? 'upload.exe';
$chunkNumber  = intval($_SERVER['HTTP_X_CHUNKNUMBER'] ?? 1);
$totalChunks  = intval($_SERVER['HTTP_X_TOTALCHUNKS'] ?? 1);

// sanitize filename
$filename = preg_replace('/[^A-Za-z0-9._-]/', '', $filename);

$filepath = $target_dir . $filename;

// append after first chunk
$mode = ($chunkNumber == 1) ? "wb" : "ab";

$input  = fopen("php://input", "rb");
$output = fopen($filepath, $mode);

stream_copy_to_stream($input, $output);

fclose($input);
fclose($output);

echo "Chunk $chunkNumber / $totalChunks OK";
?>
