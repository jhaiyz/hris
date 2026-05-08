<?php

require 'config.php';

$token = trim($_GET['token'] ?? '');
$area  = trim($_GET['area'] ?? '');

if($token=='' || $area==''){

    echo json_encode(["exists"=>false]);
    exit;

}

$stmt = $conn->prepare("
SELECT token 
FROM tbl_qr_token
WHERE token=? 
AND area=?
LIMIT 1
");

$stmt->bind_param("ss",$token,$area);

$stmt->execute();

$result = $stmt->get_result();

echo json_encode([
    "exists"=>$result->num_rows > 0
]);

?>