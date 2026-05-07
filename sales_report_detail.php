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
            <button type="button" id="showBrandSummaryBtn" class="btn btn-info ms-2">สรุปยอดตามยี่ห้อ AT LEAO LLIT</button>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">กรองข้อมูลตามยี่ห้อ:</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input brand-filter" type="checkbox" id="brandAll" value="all" checked>
                <label class="form-check-label" for="brandAll">ทั้งหมด</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input brand-filter" type="checkbox" id="brandAT" value="AT">
                <label class="form-check-label" for="brandAT">AT</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input brand-filter" type="checkbox" id="brandLEAO" value="LEAO">
                <label class="form-check-label" for="brandLEAO">LEAO</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input brand-filter" type="checkbox" id="brandLLIT" value="LLIT">
                <label class="form-check-label" for="brandLLIT">LLIT</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input brand-filter" type="checkbox" id="brandOther" value="other">
                <label class="form-check-label" for="brandOther">ยี่ห้ออื่นๆ</label>
            </div>
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

    <!-- Brand Summary Modal -->
    <div class="modal fade" id="brandSummaryModal" tabindex="-1" aria-labelledby="brandSummaryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="brandSummaryModalLabel">สรุปยอดรวม (AT + LEAO + LLIT)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-bordered table-striped" id="brandSummaryTable">
                                <thead>
                                    <tr>
                                        <th>ยี่ห้อ</th>
                                        <th>ค่าเฉลี่ยต่อเดือน<br>(12 เดือนปีที่แล้ว)</th>
                                        <th>ค่า MIN<br>(12 เดือนปีที่แล้ว)</th>
                                        <th>ค่า MAX<br>(12 เดือนปีที่แล้ว)</th>
                                        <th>เป้ารายเดือน</th>
                                        <th>ยอดขายเดือน <?php echo $thai_months[$month1['month']].' '.$month1['year']; ?></th>
                                        <th>ยอดขายเดือน <?php echo $thai_months[$month2['month']].' '.$month2['year']; ?></th>
                                        <th>ยอดขายเดือน <?php echo $thai_months[$month3['month']].' '.$month3['year']; ?></th>
                                        <th>ค่าเฉลี่ย 3 เดือน</th>
                                        <th>คาดการณ์ยอดขาย <?php echo $thai_months[$selected_month] ?? ''; ?></th>
                                        <th>เปรียบเทียบฯ<br>(คาดการณ์ - ค่าเฉลี่ย 3 เดือน)</th>
                                        <th>เปรียบเทียบยอดขาย%<br>จากค่าเฉลี่ย</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>

                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-success" id="saveBrandSummaryBtn">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

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
            
            var table = $('#salesTable').DataTable({
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

            // Brand filter checkboxes
            $('.brand-filter').on('change', function() {
                var $this = $(this);
                var value = $this.val();

                if (value === 'all' && $this.is(':checked')) {
                    $('.brand-filter').not(this).prop('checked', false);
                    $.fn.dataTable.ext.search.pop();
                    table.draw();
                    return;
                }

                if (value !== 'all' && $this.is(':checked')) {
                    $('#brandAll').prop('checked', false);
                }

                if ($('.brand-filter:checked').length === 0) {
                    $('#brandAll').prop('checked', true);
                    $.fn.dataTable.ext.search.pop();
                    table.draw();
                    return;
                }

                $.fn.dataTable.ext.search.pop();
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    var brand = data[4] || '';
                    var selected = $('.brand-filter:checked').map(function() { return $(this).val(); }).get();

                    if (selected.indexOf('all') !== -1) return true;

                    var isOther = selected.indexOf('other') !== -1 && brand !== 'AT' && brand !== 'LEAO' && brand !== 'LLIT';
                    var isDirect = selected.indexOf(brand) !== -1;

                    return isDirect || isOther;
                });
                table.draw();
            });

            // Brand Summary Modal
            function numberFormat(num) {
                return num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

                $('#showBrandSummaryBtn').on('click', function() {
                try {
                    var brands = ['AT', 'LEAO', 'LLIT'];
                    // Per-brand sums
                    var brandSums = {};
                    brands.forEach(function(brand) {
                        brandSums[brand] = {
                            avg:0, min:0, max:0, target:0, m1:0, m2:0, m3:0, avg3:0, forecast:0, compare1:0
                        };
                    });
                    // Total sum
                    var totalSum = {avg:0, min:0, max:0, target:0, m1:0, m2:0, m3:0, avg3:0, forecast:0, compare1:0};

                    // Temporarily show all rows to access DOM inputs
                    var currentPage = table.page();
                    var currentLength = table.page.len();
                    table.page.len(-1).draw(false);

                    // Function to get numeric value from TD
                    function getNum($td) {
                        if ($td.find('input').length > 0) {
                            return parseFloat($td.find('input').val().replace(/,/g,'')) || 0;
                        }
                        return parseFloat($td.text().replace(/,/g,'')) || 0;
                    }

                    // Now all rows are in DOM, read values
                    table.$('tr').each(function() {
                        var $tds = $(this).find('td');
                        if ($tds.length === 0) return;
                        var brand = $.trim($tds.eq(4).text());
                        if (brands.indexOf(brand) === -1) return;

                        // Add to per-brand sum
                        brandSums[brand].avg += getNum($tds.eq(7));
                        brandSums[brand].min += getNum($tds.eq(8));
                        brandSums[brand].max += getNum($tds.eq(9));
                        brandSums[brand].target += getNum($tds.eq(10));
                        brandSums[brand].m1 += getNum($tds.eq(11));
                        brandSums[brand].m2 += getNum($tds.eq(12));
                        brandSums[brand].m3 += getNum($tds.eq(13));
                        brandSums[brand].avg3 += getNum($tds.eq(14));
                        brandSums[brand].forecast += getNum($tds.eq(15));
                        brandSums[brand].compare1 += getNum($tds.eq(16));

                        // Add to total sum
                        totalSum.avg += getNum($tds.eq(7));
                        totalSum.min += getNum($tds.eq(8));
                        totalSum.max += getNum($tds.eq(9));
                        totalSum.target += getNum($tds.eq(10));
                        totalSum.m1 += getNum($tds.eq(11));
                        totalSum.m2 += getNum($tds.eq(12));
                        totalSum.m3 += getNum($tds.eq(13));
                        totalSum.avg3 += getNum($tds.eq(14));
                        totalSum.forecast += getNum($tds.eq(15));
                        totalSum.compare1 += getNum($tds.eq(16));
                    });

                    // Restore original pagination
                    table.page.len(currentLength).draw(false);
                    table.page(currentPage).draw(false);

                    // Populate tbody with per-brand rows
                    var $tbody = $('#brandSummaryTable tbody');
                    $tbody.empty();
                    brands.forEach(function(brand) {
                        var b = brandSums[brand];
                        var comparePercent = b.avg3 !== 0 ? ((b.forecast / b.avg3) * 100) : 0;
                        var rowHtml = '<tr>' +
                            '<td>' + brand + '</td>' +
                            '<td class="text-right">' + numberFormat(b.avg) + '</td>' +
                            '<td class="text-right">' + numberFormat(b.min) + '</td>' +
                            '<td class="text-right">' + numberFormat(b.max) + '</td>' +
                            '<td class="text-right">-</td>' +
                            '<td class="text-right">' + numberFormat(b.m1) + '</td>' +
                            '<td class="text-right">' + numberFormat(b.m2) + '</td>' +
                            '<td class="text-right">' + numberFormat(b.m3) + '</td>' +
                            '<td class="text-right">' + numberFormat(b.avg3) + '</td>' +
                            '<td class="text-right">' + numberFormat(b.forecast) + '</td>' +
                            '<td class="text-right">' + numberFormat(b.compare1) + '</td>' +
                            '<td class="text-right">' + comparePercent.toFixed(2) + '%</td>' +
                            '</tr>';
                        $tbody.append(rowHtml);
                    });

                    // Add total row as last row in tbody
                    var totalComparePercent = totalSum.avg3 !== 0 ? ((totalSum.forecast / totalSum.avg3) * 100) : 0;
                    var totalRowHtml = '<tr class="fw-bold table-primary">' +
                        '<td>รวมทั้งหมด</td>' +
                        '<td class="text-right">' + numberFormat(totalSum.avg) + '</td>' +
                        '<td class="text-right">' + numberFormat(totalSum.min) + '</td>' +
                        '<td class="text-right">' + numberFormat(totalSum.max) + '</td>' +
                        '<td class="text-right"><input type="text" class="form-control form-control-sm brand-target-all" value="' + numberFormat(totalSum.target) + '" style="width:120px;text-align:right;"></td>' +
                        '<td class="text-right">' + numberFormat(totalSum.m1) + '</td>' +
                        '<td class="text-right">' + numberFormat(totalSum.m2) + '</td>' +
                        '<td class="text-right">' + numberFormat(totalSum.m3) + '</td>' +
                        '<td class="text-right">' + numberFormat(totalSum.avg3) + '</td>' +
                        '<td class="text-right">' + numberFormat(totalSum.forecast) + '</td>' +
                        '<td class="text-right">' + numberFormat(totalSum.compare1) + '</td>' +
                        '<td class="text-right">' + totalComparePercent.toFixed(2) + '%</td>' +
                        '</tr>';
                    $tbody.append(totalRowHtml);

                    // Destroy existing DataTable if any
                    if ($.fn.DataTable.isDataTable('#brandSummaryTable')) {
                        $('#brandSummaryTable').DataTable().destroy();
                        $('#brandSummaryTable').removeAttr('style');
                    }

                    // Unbind previous event and bind new one
                    $('#brandSummaryModal').off('shown.bs.modal');
                    $('#brandSummaryModal').on('shown.bs.modal', function() {
                        // Small delay to ensure modal is fully rendered
                        setTimeout(function() {
                            var brandTable = $('#brandSummaryTable').DataTable({
                                "scrollX": true,
                                "scrollY": "55vh",
                                "scrollCollapse": true,
                                "paging": false,
                                "info": false,
                                "searching": false,
                                "language": {
                                    "emptyTable": "ไม่พบข้อมูล"
                                }
                            });
                            brandTable.columns.adjust().draw();
                        }, 100);
                    });

                    // Show modal
                    $('#brandSummaryModal').modal('show');

                $('#brandSummaryTable').off('input', '.brand-target-all').on('input', '.brand-target-all', function() {
                    var val = $(this).val();
                    table.rows({ search: 'applied' }).every(function() {
                        var rowData = this.data();
                        if (brands.indexOf($.trim(rowData[4] || '')) !== -1) {
                            $(rowData[10]).find('input').val(val).trigger('input');
                        }
                    });
                });

                // Save button in modal - save AT, LEAO, LLIT targets
                $('#saveBrandSummaryBtn').off('click').on('click', function() {
                    var updates = [];
                    var brands = ['AT', 'LEAO', 'LLIT'];

                    // Get the combined target from the total row
                    var combinedTarget = parseFloat($('#brandSummaryTable tbody tr.table-primary td').eq(4).find('input').val().replace(/,/g,'')) || 0;

                    // Temporarily show all rows
                    var currentPage = table.page();
                    var currentLength = table.page.len();
                    table.page.len(-1).draw(false);

                    table.$('tr').each(function() {
                        var $tds = $(this).find('td');
                        if ($tds.length === 0) return;

                        var brand = $.trim($tds.eq(4).text());
                        if (brands.indexOf(brand) === -1) return;

                        var $target = $tds.eq(10).find('input');
                        var $forecast = $tds.eq(15).find('input');
                        var $compare1 = $tds.eq(16).find('input');
                        var $compare2 = $tds.eq(17).find('input');
                        var $remark = $tds.eq(18).find('input');

                        var id = $target.data('id');
                        if (!id) return;

                        // Use combined target for all brands
                        $target.val(combinedTarget).trigger('input');

                        updates.push({
                            id: id,
                            target_qty: combinedTarget,
                            forecast_qty: parseFloat($forecast.val()) || 0,
                            compare_sales_1: parseFloat($compare1.val()) || 0,
                            compare_sales_2: parseFloat($compare2.val()) || 0,
                            remark: $remark.val() || ''
                        });
                    });

                    // Restore pagination
                    table.page.len(currentLength).draw(false);
                    table.page(currentPage).draw(false);

                    if (updates.length === 0) {
                        alert('ไม่มีข้อมูลที่จะบันทึก');
                        return;
                    }

                    if (!confirm('ต้องการบันทึกข้อมูล 3 ยี่ห้อ (AT, LEAO, LLIT) ทั้งหมด ' + updates.length + ' รายการใช่หรือไม่?')) {
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
                                $('#brandSummaryModal').modal('hide');
                            } else {
                                alert('เกิดข้อผิดพลาด: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('เกิดข้อผิดพลาดในการส่งข้อมูล');
                        }
                    });
                });
                } catch(e) {
                    alert('Error: ' + e.message);
                }
            });

            $('#saveBtn').click(function() {
                var updates = [];
                // Temporarily show all rows to access all inputs
                var currentPage = table.page();
                var currentLength = table.page.len();
                table.page.len(-1).draw(false);

                // Now all rows are in DOM
                table.$('tr').each(function(rowIndex) {
                    var $row = $(this);
                    var $target = $row.find('.target-qty');
                    if ($target.length === 0) return; // skip header row

                    var $forecast = $row.find('.forecast-qty');
                    var $compare1 = $row.find('.compare-sales-1');
                    var $compare2 = $row.find('.compare-sales-2');
                    var $remark = $row.find('.remark');

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

                // Restore original pagination
                table.page.len(currentLength).draw(false);
                table.page(currentPage).draw(false);
                
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
