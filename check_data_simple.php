<?php
date_default_timezone_set("Asia/Bangkok");
include('config/connect_db.php');

echo "<h2>ตรวจสอบข้อมูลในตาราง ims_product_sale_sac</h2>";

// Check years available
$sql = "SELECT DI_YEAR, COUNT(*) as cnt FROM ims_product_sale_sac GROUP BY DI_YEAR ORDER BY DI_YEAR DESC";
$stmt = $conn->query($sql);
$years = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>ปีที่มีข้อมูลในตาราง:</h3>";
echo "<pre>" . print_r($years, true) . "</pre>";

// Check if last year data exists
$last_year = date('Y') - 1;
$sql2 = "SELECT COUNT(*) as cnt FROM ims_product_sale_sac WHERE DI_YEAR = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->execute([$last_year]);
$result2 = $stmt2->fetch(PDO::FETCH_ASSOC);

echo "<h3>จำนวนข้อมูลปี $last_year: " . ($result2['cnt'] ?? 0) . " รายการ</h3>";

if ($result2['cnt'] > 0) {
    // Show sample calculation for a customer
    $sql3 = "SELECT 
                AR_CODE,
                SKU_CODE,
                SUM(TRD_QTY) as total_qty,
                COUNT(DISTINCT DI_MONTH) as num_months
            FROM ims_product_sale_sac 
            WHERE DI_YEAR = ?
            GROUP BY AR_CODE, SKU_CODE
            LIMIT 5";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->execute([$last_year]);
    $results3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>ตัวอย่างการคำนวณ (5 รายการแรก):</h3>";
    echo "<pre>" . print_r($results3, true) . "</pre>";
} else {
    echo "<p style='color:red'>ไม่พบข้อมูลปี $last_year ในตาราง! ทำให้ค่า avg/min/max ไม่ถูกคำนวณ</p>";
}

$conn = null;
?>
