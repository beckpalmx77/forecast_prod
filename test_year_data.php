<?php
date_default_timezone_set("Asia/Bangkok");
include('config/connect_db.php');

$last_year = date('Y') - 1;

// Check if data exists for last year
$sql = "SELECT 
            DI_YEAR, 
            COUNT(*) as cnt, 
            COUNT(DISTINCT AR_CODE) as num_customers,
            COUNT(DISTINCT SKU_CODE) as num_products,
            SUM(TRD_QTY) as total_qty
        FROM ims_product_sale_sac 
        WHERE DI_YEAR = ?
        GROUP BY DI_YEAR";

$stmt = $conn->prepare($sql);
$stmt->execute([$last_year]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Last year ($last_year) data:\n";
print_r($result);

// Check sample data for a specific customer
$sql2 = "SELECT AR_CODE, SKU_CODE, DI_MONTH, DI_YEAR, SUM(TRD_QTY) as qty
         FROM ims_product_sale_sac
         WHERE DI_YEAR = ?
         GROUP BY AR_CODE, SKU_CODE, DI_MONTH, DI_YEAR
         LIMIT 10";

$stmt2 = $conn->prepare($sql2);
$stmt2->execute([$last_year]);
$results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "\nSample data for $last_year:\n";
print_r($results2);

$conn = null;
?>
