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
        $sql = "UPDATE sales_report_detail SET target_qty = :target_qty WHERE id = :id";
        $stmt = $conn->prepare($sql);
        
        $conn->beginTransaction();
        
        foreach ($updates as $u) {
            $stmt->execute([
                ':target_qty' => floatval($u['target_qty']),
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
