<?php
// get_leaves.php  — AJAX endpoint for Leave filter
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Must be logged in
if (empty($_SESSION['emp_ID'])) {
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

require_once __DIR__ . '/../../db.php';

// ── Validate / sanitise dates ────────────────────────────────────────────────
$currentYear = date('Y');
$defaultFrom = $currentYear . '-01-01';
$defaultTo   = $currentYear . '-12-31';

$filterFrom = !empty($_GET['date_from']) ? $_GET['date_from'] : $defaultFrom;
$filterTo   = !empty($_GET['date_to'])   ? $_GET['date_to']   : $defaultTo;

// strtotime returns false for invalid dates
if (!strtotime($filterFrom)) $filterFrom = $defaultFrom;
if (!strtotime($filterTo))   $filterTo   = $defaultTo;

// ── Query ────────────────────────────────────────────────────────────────────
$db = getDB();

$sql = "
    SELECT
        a.app_ID,
        a.DOF,
        a.TOF,
        a.NOD,
        a.Inclusive_Dates,
        a.Status,
        a.Remarks,
        a.DOL_A,
        a.DOL_B,
        a.DOL_C,
        a.lt_ID,
        l.Description AS LeaveDescription
    FROM tblappleave a
    LEFT JOIN tbl_lt l ON a.lt_ID = l.lt_ID
    WHERE a.emp_ID = ?
      AND a.DOF BETWEEN ? AND ?
    ORDER BY
        FIELD(LOWER(a.Status),
              'pending approval',
              'hr approved',
              'management approved',
              'disapproved'),
        a.DOF DESC,
        a.TOF DESC
";

$stmt = $db->prepare($sql);
$stmt->bind_param('iss', $_SESSION['emp_ID'], $filterFrom, $filterTo);
$stmt->execute();
$res  = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

$stmt->close();
$db->close();

echo json_encode(['rows' => $rows]);