<?php
date_default_timezone_set("Asia/Bangkok");
include('config/connect_db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
        exit;
    }
    
    try {
        $conn->beginTransaction();
        
        // Delete details first
        $sql_del_det = "DELETE FROM sales_report_detail WHERE header_id = :id";
        $stmt_del_det = $conn->prepare($sql_del_det);
        $stmt_del_det->execute([':id' => $id]);
        
        // Delete header
        $sql_del_hdr = "DELETE FROM sales_report_header WHERE id = :id";
        $stmt_del_hdr = $conn->prepare($sql_del_hdr);
        $stmt_del_hdr->execute([':id' => $id]);
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
}
$conn = null;
?>
