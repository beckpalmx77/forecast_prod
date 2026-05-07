<?php
date_default_timezone_set("Asia/Bangkok");
include('config/connect_db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = json_decode($_POST['updates'] ?? '[]', true);
    
    if (!is_array($updates) || empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีข้อมูลที่จะบันทึก']);
        exit;
    }
    
    try {
        $sql = "UPDATE sales_report_detail 
                SET target_qty = :target_qty, forecast_qty = :forecast_qty,
                    compare_sales_1 = :compare_sales_1, compare_sales_2 = :compare_sales_2,
                    remark = :remark
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        
        $conn->beginTransaction();
        
        foreach ($updates as $u) {
            $stmt->execute([
                ':target_qty' => floatval($u['target_qty'] ?? 0),
                ':forecast_qty' => floatval($u['forecast_qty'] ?? 0),
                ':compare_sales_1' => floatval($u['compare_sales_1'] ?? 0),
                ':compare_sales_2' => floatval($u['compare_sales_2'] ?? 0),
                ':remark' => $u['remark'] ?? '',
                ':id' => intval($u['id'])
            ]);
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
}
$conn = null;
?>
