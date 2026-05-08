<?php
// delete_file.php

$target_dir = "C:/xampp/htdocs/hris/photos/";

// Get filename from POST
$uniqueName = $_POST['uniqueName'] ?? '';
$uniqueName = preg_replace('/[^A-Za-z0-9._-]/', '', $uniqueName); // sanitize

$filepath = $target_dir . $uniqueName;

if(file_exists($filepath)){
    if(unlink($filepath)){
        echo json_encode([
            'status' => 'ok',
            'message' => 'File deleted'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Cannot delete file'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'File not found'
    ]);
}
?>