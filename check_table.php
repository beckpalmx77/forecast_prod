<?php
date_default_timezone_set("Asia/Bangkok");
include('config/db_value.inc');

try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=".DB_PORT, DB_USER, DB_PASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== SHOW COLUMNS FROM sales_report_detail ===\n\n";
    $stmt = $conn->query("SHOW COLUMNS FROM sales_report_detail");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Column names exactly as they appear:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n=== Checking specific columns ===\n";
    $columnNames = array_column($columns, 'Field');
    
    echo "1. sku_code column: " . (in_array('sku_code', $columnNames) ? 'YES - exactly "sku_code"' : 'NOT FOUND') . "\n";
    echo "2. sku_name column: " . (in_array('sku_name', $columnNames) ? 'YES - exactly "sku_name"' : 'NOT FOUND') . "\n";
    
    echo "\n=== Testing INSERT with valid header_id ===\n";
    
    // First check if there's an existing header
    $stmt = $conn->query("SELECT id FROM sales_report_header LIMIT 1");
    $header = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($header) {
        $testHeaderId = $header['id'];
        echo "Using existing header_id: $testHeaderId\n";
    } else {
        // Create a test header with correct columns
        echo "No existing header found. Creating test header...\n";
        $conn->exec("INSERT INTO sales_report_header (ar_code, ar_name, report_month, report_year, created_at) VALUES ('TEST', 'TEST', 1, 2026, NOW())");
        $testHeaderId = $conn->lastInsertId();
        echo "Created test header with id: $testHeaderId\n";
    }
    
    $sql = "INSERT INTO sales_report_detail 
        (header_id, brand_code, sku_code, sku_name, 
         qty_month1, qty_month2, qty_month3, 
         avg_3month, avg_monthly_last_year, 
         min_qty_last_year, max_qty_last_year,
         target_qty, forecast_qty, compare_sales_1, compare_sales_2, remark,
         sales_person, take_person) 
       VALUES 
       ($testHeaderId, 'TEST', 'TEST', 'TEST', 
        0, 0, 0, 
        0, 0, 
        0, 0,
        0, 0, 0, 0, 'TEST',
        'TEST', 'TEST')";
    
    try {
        $conn->exec($sql);
        echo "INSERT SUCCESSFUL!\n";
        
        $lastId = $conn->lastInsertId();
        echo "Inserted row ID: " . $lastId . "\n";
        
        // Verify the insert
        $stmt = $conn->query("SELECT * FROM sales_report_detail WHERE id = $lastId");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nVerified inserted row:\n";
        foreach ($row as $key => $val) {
            echo "  $key: $val\n";
        }
        
        // Delete the test row
        $conn->exec("DELETE FROM sales_report_detail WHERE id = " . $lastId);
        echo "\nTest row deleted from sales_report_detail.\n";
        
        // If we created a test header, delete it too
        if (!$header) {
            $conn->exec("DELETE FROM sales_report_header WHERE id = " . $testHeaderId);
            echo "Test header deleted from sales_report_header.\n";
        }
        
    } catch (PDOException $e) {
        echo "INSERT FAILED!\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "Connection Error: " . $e->getMessage();
    exit;
}
?>
