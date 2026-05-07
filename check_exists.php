<?php
date_default_timezone_set("Asia/Bangkok");
include('config/connect_db.php');

$ar_code = $_GET['ar_code'] ?? '';
$month = intval($_GET['month'] ?? 0);
$year = intval($_GET['year'] ?? 0);

if ($month <= 0 || $year <= 0) {
    echo json_encode(['exists' => false]);
    exit;
}

$sql = "SELECT id FROM sales_report_header 
        WHERE " . ($ar_code ? "ar_code = :ar_code" : "ar_code IS NULL") . " 
        AND report_month = :month AND report_year = :year";

$params = [':month' => $month, ':year' => $year];
if ($ar_code) {
    $params[':ar_code'] = $ar_code;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo json_encode(['exists' => true, 'header_id' => $row['id']]);
} else {
    echo json_encode(['exists' => false]);
}
$conn = null;
?>
