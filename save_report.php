<?php
date_default_timezone_set("Asia/Bangkok");
include('config/connect_db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ar_code = $_POST['ar_code'] ?? '';
    $ar_name = $_POST['ar_name'] ?? '';
    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? 0);
    $details = $_POST['details'] ?? '[]';

    if ($month <= 0 || $year <= 0) {
        echo json_encode(['success' => false, 'message' => 'กรุณาเลือกเดือนและปี']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Check if header already exists
        if (empty($ar_code)) {
            $sql_check = "SELECT id FROM sales_report_header 
                         WHERE ar_code IS NULL 
                         AND report_month = ? AND report_year = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute([$month, $year]);
        } else {
            $sql_check = "SELECT id FROM sales_report_header 
                         WHERE ar_code = ? 
                         AND report_month = ? AND report_year = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute([$ar_code, $month, $year]);
        }
        $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Delete old details
            $sql_del = "DELETE FROM sales_report_detail WHERE header_id = ?";
            $stmt_del = $conn->prepare($sql_del);
            $stmt_del->execute([$existing['id']]);
            $header_id = $existing['id'];
        } else {
            // Insert new header
            if (empty($ar_code)) {
                $sql_ins = "INSERT INTO sales_report_header (ar_name, report_month, report_year) 
                            VALUES (?, ?, ?)";
                $stmt_ins = $conn->prepare($sql_ins);
                $stmt_ins->execute([
                    empty($ar_name) ? null : $ar_name,
                    $month,
                    $year
                ]);
            } else {
                $sql_ins = "INSERT INTO sales_report_header (ar_code, ar_name, report_month, report_year) 
                            VALUES (?, ?, ?, ?)";
                $stmt_ins = $conn->prepare($sql_ins);
                $stmt_ins->execute([
                    $ar_code,
                    empty($ar_name) ? null : $ar_name,
                    $month,
                    $year
                ]);
            }
            $header_id = $conn->lastInsertId();
        }

        // Insert details - Fixed: 18 placeholders
        $details_arr = json_decode($details, true);
        if (is_array($details_arr)) {
            $sql_det = "INSERT INTO sales_report_detail 
                        (header_id, brand_code, sku_code, sku_name, 
                         qty_month1, qty_month2, qty_month3, 
                         avg_3month, avg_monthly_last_year, 
                         min_qty_last_year, max_qty_last_year,
                         target_qty, forecast_qty, compare_sales_1, compare_sales_2, remark,
                         sales_person, take_person) 
                        VALUES 
                        (?, ?, ?, ?, 
                         ?, ?, ?, 
                         ?, ?, 
                         ?, ?,
                         ?, ?, ?, ?, ?,
                         ?, ?)";
            // Count: 4+3+2+2+5+2 = 18 placeholders ✓
            $stmt_det = $conn->prepare($sql_det);

            foreach ($details_arr as $row) {
                $params = [
                    $header_id,                                    // 1
                    $row[2] ?? null,                            // 2: brand (from BRAND in ims_data_sale_sac_all)
                    $row[3] ?? '',                              // 3: sku_code
                    $row[4] ?? null,                            // 4: sku_name
                    floatval(str_replace(',', '', $row[9] ?? 0)),   // 5: qty_month1
                    floatval(str_replace(',', '', $row[10] ?? 0)),  // 6: qty_month2
                    floatval(str_replace(',', '', $row[11] ?? 0)),  // 7: qty_month3
                    floatval(str_replace(',', '', $row[12] ?? 0)),  // 8: avg_3month
                    floatval(str_replace(',', '', $row[5] ?? 0)),   // 9: avg_monthly_last_year
                    floatval(str_replace(',', '', $row[6] ?? 0)),   // 10: min_qty_last_year
                    floatval(str_replace(',', '', $row[7] ?? 0)),   // 11: max_qty_last_year
                    floatval(str_replace(',', '', $row[8] ?? 0)),   // 12: target_qty
                    floatval(str_replace(',', '', $row[13] ?? 0)),  // 13: forecast_qty
                    floatval(str_replace(',', '', $row[14] ?? 0)),  // 14: compare_sales_1
                    floatval(str_replace(',', '', $row[15] ?? 0)),  // 15: compare_sales_2
                    $row[16] ?? '',                             // 16: remark
                    $row[17] ?? '',                             // 17: sales_person
                    $row[18] ?? ''                              // 18: take_person
                ];
                
                if (count($params) !== 18) {
                    error_log("Parameter count: " . count($params) . " for 18 placeholders");
                }
                
                $stmt_det->execute($params);
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
        } catch (Exception $e) {
            $conn->rollBack();
            $error_info = [
                'success' => false, 
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'sql_det' => $sql_det ?? null,
                'params_count' => isset($params) ? count($params) : 'unknown',
                'details_count' => isset($details_arr) ? count($details_arr) : 'unknown'
            ];
            echo json_encode($error_info);
        }
}
$conn = null;
?>
