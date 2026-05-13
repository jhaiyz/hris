<?php
require_once __DIR__ . '/../db.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'fetch';
$emp_ID = (int)$_SESSION['emp_ID'];

$db = getDB();

if ($action === 'fetch') {
    $stmt = $db->prepare("
        SELECT
            notification_ID,
            Title,
            Status,
            is_Read,
            Created_At
        FROM tblnotification
        WHERE sender_ID = ?
          AND is_Read = 0
          AND Route = 'from hr'
        ORDER BY Created_At DESC
        LIMIT 30
    ");
    $stmt->bind_param('i', $emp_ID);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    $unread = 0;
    while ($row = $result->fetch_assoc()) {
        $unread++;
        $notifications[] = [
            'id'         => (int)$row['notification_ID'],
            'title'      => $row['Title'],
            'status'     => $row['Status'],
            'is_read'    => (int)$row['is_Read'],
            'created_at' => $row['Created_At'],
        ];
    }
    $stmt->close();
    $db->close();

    echo json_encode([
        'success'       => true,
        'unread'        => $unread,
        'notifications' => $notifications,
    ]);

} elseif ($action === 'mark_read') {
    $notif_id = (int)($_GET['id'] ?? 0);
    if ($notif_id > 0) {
        $stmt = $db->prepare("
            UPDATE tblnotification
            SET is_Read = 1
            WHERE notification_ID = ? AND sender_ID = ? AND Route = 'from hr'
        ");
        $stmt->bind_param('ii', $notif_id, $emp_ID);
        $stmt->execute();
        $stmt->close();
    }
    $db->close();
    echo json_encode(['success' => true]);

} elseif ($action === 'mark_all_read') {
    $stmt = $db->prepare("
        UPDATE tblnotification
        SET is_Read = 1
        WHERE sender_ID = ? AND Route = 'from hr' AND is_Read = 0
    ");
    $stmt->bind_param('i', $emp_ID);
    $stmt->execute();
    $stmt->close();
    $db->close();
    echo json_encode(['success' => true]);

} else {
    $db->close();
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}