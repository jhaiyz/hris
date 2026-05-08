<?php
// Config.php — clean, minimal

ini_set('display_errors',0);
ini_set('display_startup_errors',0);
error_reporting(0);

$host = "localhost";
$db   = "hris";
$user = "root";
$pass = "";

// Connect to database
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error){
    header('Content-Type: application/json');
    echo json_encode(["success"=>false,"message"=>"DB connection failed: ".$conn->connect_error]);
    exit;
}

// Start session safely
if(session_status()===PHP_SESSION_NONE) session_start();