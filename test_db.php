<?php
require 'config.php';

$sql = "SELECT token, Area, Date_Expire FROM tbl_qr_token ORDER BY Date_Expire DESC";

$result = $conn->query($sql);

while($row = $result->fetch_assoc()){
    echo $row['token'] . " | " . $row['Area'] . " | " . $row['Date_Expire'] . "<br>";
}
?>