<?php
require_once __DIR__ . '/../db.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'fetch';
$emp_ID = (int)$_SESSION['emp_ID'];

$db = getDB();

if ($action === 'fetch') {
    // Fetch unread count + latest notifications (from HR only)
    $stmt = $db->prepare("
        SELECT
            notif_ID,
            Title,
            Status,
            Is_Read,
            Created_At
        FROM tblnotification
        WHERE Sender_ID = ?
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
        if ($row['Is_Read'] == 0) $unread++;
        $notifications[] = [
            'id'         => (int)$row['notif_ID'],
            'title'      => $row['Title'],
            'status'     => $row['Status'],
            'is_read'    => (int)$row['Is_Read'],
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
    // Mark a single notification as read
    $notif_id = (int)($_GET['id'] ?? 0);
    if ($notif_id > 0) {
        $stmt = $db->prepare("
            UPDATE tblnotification
            SET Is_Read = 1
            WHERE notif_ID = ? AND Sender_ID = ? AND Route = 'from hr'
        ");
        $stmt->bind_param('ii', $notif_id, $emp_ID);
        $stmt->execute();
        $stmt->close();
    }
    $db->close();
    echo json_encode(['success' => true]);

} elseif ($action === 'mark_all_read') {
    // Mark all notifications as read
    $stmt = $db->prepare("
        UPDATE tblnotification
        SET Is_Read = 1
        WHERE Sender_ID = ? AND Route = 'from hr' AND Is_Read = 0
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