<?php
date_default_timezone_set("Asia/Bangkok");
include('config/connect_db.php');

$last_year = date('Y') - 1;

echo "<h2>ตรวจสอบข้อมูลปี $last_year ในตาราง ims_product_sale_sac</h2>";

// Check if any data exists for last year
$sql = "SELECT COUNT(*) as total_records, 
               COUNT(DISTINCT AR_CODE) as num_customers,
               COUNT(DISTINCT SKU_CODE) as num_products,
               MIN(DI_MONTH) as min_month,
               MAX(DI_MONTH) as max_month
        FROM ims_product_sale_sac 
        WHERE DI_YEAR = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$last_year]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>ข้อมูลปี $last_year:</h3>";
echo "<pre>" . print_r($result, true) . "</pre>";

if ($result['total_records'] > 0) {
    // Show sample calculation
    $sql2 = "SELECT 
                AR_CODE,
                SKU_CODE,
                SUM(TRD_QTY) as total_qty,
                COUNT(DISTINCT DI_MONTH) as num_months,
                SUM(TRD_QTY) / 12 as avg_wrong,
                SUM(TRD_QTY) / COUNT(DISTINCT DI_MONTH) as avg_correct
            FROM ims_product_sale_sac
            WHERE DI_YEAR = ?
            GROUP BY AR_CODE, SKU_CODE
            LIMIT 10";
    
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute([$last_year]);
    $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>ตัวอย่างการคำนวณ (แสดง 10 รายการ):</h3>";
    echo "<pre>" . print_r($results2, true) . "</pre>";
} else {
    echo "<p style='color:red'>ไม่พบข้อมูลปี $last_year ในตาราง!</p>";
    echo "<p>ตรวจสอบปีที่มีข้อมูลในตาราง:</p>";
    
    $sql3 = "SELECT DI_YEAR, COUNT(*) as cnt 
             FROM ims_product_sale_sac 
             GROUP BY DI_YEAR 
             ORDER BY DI_YEAR DESC";
    $stmt3 = $conn->query($sql3);
    $years = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($years, true) . "</pre>";
}

$conn = null;
?>
