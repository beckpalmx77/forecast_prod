<?php
date_default_timezone_set("Asia/Bangkok");
include('config/connect_db.php');

// Fetch all headers
$sql = "SELECT h.*, 
        (SELECT COUNT(*) FROM sales_report_detail d WHERE d.header_id = h.id) as total_items
        FROM sales_report_header h 
        ORDER BY h.report_year DESC, h.report_month DESC, h.id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$headers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$conn = null;

$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
    4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
    10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการบันทึกยอดขาย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { font-family: sans-serif; margin: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="my-4">รายการบันทึกวางแผนเป้ายอดขาย</h2>
        <a href="sales_report" class="btn btn-primary mb-3">สร้างรายงานใหม่</a>
        
        <table id="headerTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>รหัสร้านค้า</th>
                    <th>ชื่อร้านค้า</th>
                    <th>เดือน/ปี</th>
                    <th>จำนวนรายการ</th>
                    <th>วันที่สร้าง</th>
                    <th>action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($headers as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['ar_code'] ?? '-') ?></td>
                    <td class="text-start"><?= htmlspecialchars($h['ar_name'] ?? '-') ?></td>
                    <td><?= $thai_months[$h['report_month']] . ' ' . $h['report_year'] ?></td>
                    <td class="text-center"><?= $h['total_items'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                    <td>
                        <a href="sales_report_detail?load_id=<?= $h['id'] ?>" class="btn btn-sm btn-info">ดูรายละเอียด</a>
                        <button class="btn btn-sm btn-danger" onclick="deleteReport(<?= $h['id'] ?>)">ลบ</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#headerTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [5, 10, 25, 50],
                "language": {
                    "lengthMenu": "แสดง _MENU_ รายการ",
                    "zeroRecords": "ไม่พบข้อมูล",
                    "info": "แสดงหน้า _PAGE_ จาก _PAGES_",
                    "infoEmpty": "ไม่มีข้อมูล",
                    "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
                    "search": "ค้นหา:",
                    "paginate": {
                        "first": "หน้าแรก",
                        "last": "หน้าสุดท้าย",
                        "next": "ถัดไป",
                        "previous": "ก่อนหน้า"
                    }
                }
            });
        });

        function deleteReport(id) {
            if (confirm('ต้องการลบรายงานนี้ใช่หรือไม่?')) {
                $.post('delete_report', {id: id}, function(response) {
                    if (response.success) {
                        alert('ลบเรียบร้อยแล้ว');
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }
                }, 'json');
            }
        }
    </script>
</body>
</html>
