$stmt = $conn->prepare("
SELECT Qr_Att 
FROM tblemp 
WHERE Employee_No=?
");

$stmt->bind_param("s",$employeeNo);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode([
 "allowed"=>$row["Qr_Att"] ?? 0
]);