<?php
date_default_timezone_set("Asia/Bangkok");
include('config/connect_db.php');

$last_year = date('Y') - 1;
$selected_ar_code = $_GET['ar_code'] ?? 'SAC001'; // เปลี่ยนเป็นรหัสร้านที่คุณทดสอบ

echo "<h3>ตรวจสอบข้อมูลปี $last_year</h3>";

// Check if data exists for last year
$sql1 = "SELECT COUNT(*) as cnt, 
                COUNT(DISTINCT AR_CODE) as num_customers,
                COUNT(DISTINCT SKU_CODE) as num_products,
                MIN(DI_MONTH) as min_month,
                MAX(DI_MONTH) as max_month
         FROM ims_product_sale_sac 
         WHERE DI_YEAR = ? AND AR_CODE = ?";

$stmt1 = $conn->prepare($sql1);
$stmt1->execute([$last_year, $selected_ar_code]);
$result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
echo "<p>ข้อมูลปี $last_year สำหรับ $selected_ar_code:</p>";
echo "<pre>" . print_r($result1, true) . "</pre>";

// Check sample data
$sql2 = "SELECT DI_MONTH, SKU_CODE, SUM(TRD_QTY) as total_qty
         FROM ims_product_sale_sac
         WHERE DI_YEAR = ? AND AR_CODE = ?
         GROUP BY DI_MONTH, SKU_CODE
         ORDER BY SKU_CODE, DI_MONTH
         LIMIT 20";

$stmt2 = $conn->prepare($sql2);
$stmt2->execute([$last_year, $selected_ar_code]);
$results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "<p>ตัวอย่างข้อมูลปี $last_year:</p>";
echo "<pre>" . print_r($results2, true) . "</pre>";

// Test the actual calculation
$sql3 = "SELECT 
    SKU_CODE,
    SUM(CASE WHEN DI_YEAR = ? THEN CAST(TRD_QTY AS DECIMAL(10,2)) ELSE 0 END) / 12 as avg_calc,
    SUM(CASE WHEN DI_YEAR = ? THEN CAST(TRD_QTY AS DECIMAL(10,2)) ELSE 0 END) as total_qty,
    MIN(CASE WHEN DI_YEAR = ? THEN CAST(TRD_QTY AS DECIMAL(10,2)) ELSE NULL END) as min_qty,
    MAX(CASE WHEN DI_YEAR = ? THEN CAST(TRD_QTY AS DECIMAL(10,2)) ELSE NULL END) as max_qty,
    COUNT(CASE WHEN DI_YEAR = ? THEN 1 ELSE NULL END) as num_records
FROM ims_product_sale_sac
WHERE AR_CODE = ? AND DI_YEAR = ?
GROUP BY SKU_CODE
LIMIT 10";

$stmt3 = $conn->prepare($sql3);
$stmt3->execute([$last_year, $last_year, $last_year, $last_year, $last_year, $selected_ar_code, $last_year]);
$results3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);

echo "<p>การคำนวณค่าเฉลี่ยรายเดือน (หาร 12):</p>";
echo "<pre>" . print_r($results3, true) . "</pre>";

$conn = null;
?>
