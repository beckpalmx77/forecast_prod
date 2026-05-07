<?php
date_default_timezone_set("Asia/Bangkok");

$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
    4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
    10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$load_id = isset($_GET['load_id']) ? intval($_GET['load_id']) : 0;

if ($load_id <= 0) {
    die('ไม่พบข้อมูล');
}

include('config/connect_db.php');
$sql_header = "SELECT * FROM sales_report_header WHERE id = :id";
$stmt_header = $conn->prepare($sql_header);
$stmt_header->execute([':id' => $load_id]);
$header = $stmt_header->fetch(PDO::FETCH_ASSOC);

if (!$header) {
    die('ไม่พบข้อมูล');
}

$selected_month = $header['report_month'];
$selected_year = $header['report_year'];
$selected_ar_code = $header['ar_code'];
$ar_name = $header['ar_name'];

// Calculate 3 months back
$month3_month = $selected_month - 1;
$month3_year = $selected_year;
if ($month3_month <= 0) { $month3_month += 12; $month3_year--; }
$month3 = ['month' => $month3_month, 'year' => $month3_year];

$month2_month = $selected_month - 2;
$month2_year = $selected_year;
while ($month2_month <= 0) { $month2_month += 12; $month2_year--; }
$month2 = ['month' => $month2_month, 'year' => $month2_year];

$month1_month = $selected_month - 3;
$month1_year = $selected_year;
while ($month1_month <= 0) { $month1_month += 12; $month1_year--; }
$month1 = ['month' => $month1_month, 'year' => $month1_year];

        $sql_details = "SELECT d.*, h.ar_name FROM sales_report_detail d LEFT JOIN sales_report_header h ON d.header_id = h.id WHERE d.header_id = :header_id";
        
        // Helper function to round avg_3month
        function round_avg_3month($value) {
            $value = floatval($value);
            $decimal = $value - floor($value);
            if ($decimal >= 0.5) {
                return ceil($value);
            } else {
                return floor($value);
            }
        }
$stmt_details = $conn->prepare($sql_details);
$stmt_details->execute([':header_id' => $load_id]);
$loaded_data = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
$conn = null;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดรายงานยอดขาย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" rel="stylesheet">

    <style>
        .container-fluid {
            max-width: 100%;
        }
        .table-responsive {
            position: relative;
        }
        th:nth-child(1), td:nth-child(1) {
            position: sticky;
            left: 0;
            background-color: white;
            z-index: 999;
        }
        .text-right {
            text-align: right;
        }
        th, td {
            white-space: nowrap;
            font-size: 14px;
            padding: 4px 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-5">
        <?php $display_name = $ar_name ?? ''; ?>
        <h2 class="my-4">รายละเอียดรายงานยอดขาย - <?php echo htmlspecialchars($display_name); ?> (<?php echo $thai_months[$selected_month] ?? ''; ?> <?php echo $selected_year; ?>)</h2>
        
        <div class="mb-3">
            <a href="report_list.php" class="btn btn-outline-secondary">กลับ</a>
            <button type="button" id="saveBtn" class="btn btn-success">บันทึกการแก้ไข</button>
        </div>

        <div class="table-responsive">
        <table id="salesTable" class="table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>รหัสร้านค้า</th>
                    <th>ชื่อร้านค้า</th>
                    <th>เซลส์</th>
                    <th>เทค</th>
                    <th>ยี่ห้อ</th>
                    <th>รหัสสินค้า</th>
                    <th>ชื่อสินค้า</th>
                    <th>ค่าเฉลี่ยต่อเดือน<br>(12 เดือนปีที่แล้ว)</th>
                    <th>ค่า MIN<br>(12 เดือนปีที่แล้ว)</th>
                    <th>ค่า MAX<br>(12 เดือนปีที่แล้ว)</th>
                    <th>เป้ารายเดือน</th>
                    <th>ยอดขายเดือน <?php echo $thai_months[$month1['month']].' '.$month1['year']; ?></th>
                    <th>ยอดขายเดือน <?php echo $thai_months[$month2['month']].' '.$month2['year']; ?></th>
                    <th>ยอดขายเดือน <?php echo $thai_months[$month3['month']].' '.$month3['year']; ?></th>
                    <th>ค่าเฉลี่ย 3 เดือน</th>
                    <th>คาดการณ์ยอดขาย <?php echo $thai_months[$selected_month] ?? ''; ?></th>
                    <th>เปรียบเทียบเทียบคาดการณ์จำนวนเส้น กับ ค่าเฉลี่ย 3 เดือน</th>
                    <th>เปรียบเทียบยอดขาย%จากค่าเฉลี่ย</th>
                    <th>หมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loaded_data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($selected_ar_code ?? ''); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($row['ar_name'] ?? ''); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($row['sales_person'] ?? ''); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($row['take_person'] ?? ''); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($row['brand_code'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['sku_code'] ?? ''); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($row['sku_name'] ?? ''); ?></td>
                    <td class="text-right"><?php echo number_format($row['avg_monthly_last_year'] ?? 0, 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['min_qty_last_year'] ?? 0, 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['max_qty_last_year'] ?? 0, 2); ?></td>
                    <td><input type="text" class="form-control form-control-sm target-qty" value="<?= number_format($row['target_qty'] ?? 0, 2) ?>" data-id="<?= $row['id'] ?>" style="width:80px;text-align:right;"></td>
                    <td class="text-right"><?php echo number_format($row['qty_month1'] ?? 0, 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['qty_month2'] ?? 0, 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['qty_month3'] ?? 0, 2); ?></td>
                    <td class="text-right"><?php echo number_format(round_avg_3month($row['avg_3month'] ?? 0), 2); ?></td>
                    <td><input type="text" class="form-control form-control-sm forecast-qty" value="<?= number_format($row['forecast_qty'] ?? 0, 2) ?>" data-id="<?= $row['id'] ?>" style="width:80px;text-align:right;"></td>
                    <td><input type="text" class="form-control form-control-sm compare-sales-1" value="<?= number_format($row['compare_sales_1'] ?? 0, 2) ?>" data-id="<?= $row['id'] ?>" style="width:80px;text-align:right;"></td>
                    <td><input type="text" class="form-control form-control-sm compare-sales-2" value="<?= number_format($row['compare_sales_2'] ?? 0, 2) ?>" data-id="<?= $row['id'] ?>" style="width:80px;text-align:right;"></td>
                    <td><input type="text" class="form-control form-control-sm remark" value="<?= htmlspecialchars($row['remark'] ?? '') ?>" data-id="<?= $row['id'] ?>" style="width:150px;"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Auto-calculate เทียบเทียบคาดการณ์จำนวนเส้น กับ ค่าเฉลี่ย 3 เดือน when forecast_qty changes
            // avg_3month is already rounded in PHP (round up if decimal >= 0.5, round down if < 0.5)
            $('#salesTable tbody').on('input', '.forecast-qty', function() {
                var $row = $(this).closest('tr');
                var forecastVal = parseFloat($(this).val()) || 0;
                var avg3MonthText = $row.find('td').eq(14).text().replace(/,/g, ''); // column 14 in HTML = avg_3month (rounded)
                var avg3Month = parseFloat(avg3MonthText) || 0;
                var compare1 = forecastVal - avg3Month;
                $row.find('.compare-sales-1').val(compare1.toFixed(2));
                
                // Calculate เปรียบเทียบยอดขาย%จากค่าเฉลี่ย = (forecast_qty - avg_3month) / avg_3month * 100
                var compare2 = (avg3Month !== 0) ? ((forecastVal - avg3Month) / avg3Month) * 100 : 0;
                $row.find('.compare-sales-2').val(compare2.toFixed(2));
            });
            
            $('#salesTable').DataTable({
                "lengthMenu": [[12, 30, 50, 100], [12, 30, 50, 100]],
                "pageLength": 12,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        title: '<?php echo $thai_months[$selected_month]."-".$selected_year; ?>',
                        text: 'Export Excel'
                    }
                ],
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
            
            $('#saveBtn').click(function() {
                var updates = [];
                $('tr').each(function(rowIndex) {
                    var $row = $(this);
                    var $target = $row.find('.target-qty');
                    var $forecast = $row.find('.forecast-qty');
                    var $compare1 = $row.find('.compare-sales-1');
                    var $compare2 = $row.find('.compare-sales-2');
                    var $remark = $row.find('.remark');
                    
                    if ($target.length === 0) return; // skip header row
                    
                    var id = $target.data('id') || $forecast.data('id');
                    if (!id) return;
                    
                    updates.push({
                        id: id,
                        target_qty: parseFloat($target.val()) || 0,
                        forecast_qty: parseFloat($forecast.val()) || 0,
                        compare_sales_1: parseFloat($compare1.val()) || 0,
                        compare_sales_2: parseFloat($compare2.val()) || 0,
                        remark: $remark.val() || ''
                    });
                });
                
                if (updates.length === 0) {
                    alert('ไม่มีข้อมูลที่จะบันทึก');
                    return;
                }
                
                if (!confirm('ต้องการบันทึกข้อมูลทั้งหมด ' + updates.length + ' รายการใช่หรือไม่?')) {
                    return;
                }
                
                $.ajax({
                    url: 'update_forecast.php',
                    type: 'POST',
                    data: { updates: JSON.stringify(updates) },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('เกิดข้อผิดพลาดในการส่งข้อมูล');
                    }
                });
            });
        });
    </script>
</body>
</html>
