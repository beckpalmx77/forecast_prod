<?php
date_default_timezone_set("Asia/Bangkok");

$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
    4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
    10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selected_ar_code = isset($_GET['ar_code']) ? $_GET['ar_code'] : '';
$load_id = isset($_GET['load_id']) ? intval($_GET['load_id']) : 0;

// If load_id is set, load from database
if ($load_id > 0) {
    include('config/connect_db.php');
    $sql_header = "SELECT * FROM sales_report_header WHERE id = :id";
    $stmt_header = $conn->prepare($sql_header);
    $stmt_header->execute([':id' => $load_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);
    
    if ($header) {
        $selected_month = $header['report_month'];
        $selected_year = $header['report_year'];
        $selected_ar_code = $header['ar_code'];
        
        $sql_details = "SELECT d.*, h.ar_name FROM sales_report_detail d 
                      LEFT JOIN sales_report_header h ON d.header_id = h.id
                      WHERE d.header_id = :header_id";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->execute([':header_id' => $load_id]);
        $loaded_data = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
    }
    $conn = null;
}

// Calculate 3 months back from selected month (exclude selected month)
$month3_month = $selected_month - 1;
$month3_year = $selected_year;
if ($month3_month <= 0) {
    $month3_month += 12;
    $month3_year--;
}
$month3 = ['month' => $month3_month, 'year' => $month3_year];

$month2_month = $selected_month - 2;
$month2_year = $selected_year;
while ($month2_month <= 0) {
    $month2_month += 12;
    $month2_year--;
}
$month2 = ['month' => $month2_month, 'year' => $month2_year];

$month1_month = $selected_month - 3;
$month1_year = $selected_year;
while ($month1_month <= 0) {
    $month1_month += 12;
    $month1_year--;
}
$month1 = ['month' => $month1_month, 'year' => $month1_year];

// Fetch customers for dropdown
include('config/connect_db.php');
$sql_customers = "SELECT customer_id, f_name FROM ims_customer_ar WHERE customer_id LIKE 'SAC%' ORDER BY f_name";
$stmt_customers = $conn->prepare($sql_customers);
$stmt_customers->execute();
$customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);
$conn = null;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานยอดขาย 3 เดือนย้อนหลัง</title>
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
        .autocomplete-suggestion {
            padding: 8px;
            cursor: pointer;
        }
        .autocomplete-suggestion:hover,
        .autocomplete-suggestion.active {
            background-color: #e8e8e8;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-5">
        <h2 class="my-4">รายงานยอดขาย 3 เดือน (เริ่มจากเดือนที่เลือก)</h2>
        <form id="searchForm" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">เลือกเดือน:</label>
                <select name="month" id="monthSelect" class="form-select">
                    <?php foreach ($thai_months as $num => $name): ?>
                        <option value="<?= $num ?>" <?= ($num == $selected_month) ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">เลือกปี:</label>
                <select name="year" id="yearSelect" class="form-select">
                    <?php
                    $current_y = date('Y');
                    for ($y = $current_y - 2; $y <= $current_y + 1; $y++):
                    ?>
                        <option value="<?= $y ?>" <?= ($y == $selected_year) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4 position-relative">
                <label class="form-label">ค้นหาร้านค้า:</label>
                <input type="text" id="customerSearch" class="form-control" placeholder="พิมพ์ชื่อร้านค้า..." autocomplete="off">
                <input type="hidden" name="ar_code" id="arCode" value="<?= htmlspecialchars($selected_ar_code) ?>">
                <div id="suggestions" class="autocomplete-suggestions" style="display:none;"></div>
            </div>
            <div class="col-md-3 align-self-end">
                <a href="report_list.php" class="btn btn-outline-secondary">กลับ</a>
                <button type="button" id="searchBtn" class="btn btn-primary">ค้นหา</button>
                <button type="button" id="saveBtn" class="btn btn-success">บันทึก</button>
                <button type="button" id="resetBtn" class="btn btn-secondary">ล้าง</button>
            </div>
        </form>

        <?php if ($load_id >0 && !empty($loaded_data)): ?>
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
                    <th>ยอดขายเดือน <?= $thai_months[$month1['month']].' '.$month1['year'] ?></th>
                    <th>ยอดขายเดือน <?= $thai_months[$month2['month']].' '.$month2['year'] ?></th>
                    <th>ยอดขายเดือน <?= $thai_months[$month3['month']].' '.$month3['year'] ?></th>
                    <th>ค่าเฉลี่ย 3 เดือน</th>
                    <th>คาดการณ์ยอดขาย <?php echo $thai_months[$selected_month] ?? ''; ?></th>
                    <th>เทียบเทียบคาดการณ์จำนวนเส้น กับ ค่าเฉลี่ย 3 เดือน</th>
                    <th>เทียบยอดขาย%จากค่าเฉลี่ย</th>
                    <th>หมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loaded_data as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($selected_ar_code) ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['ar_name'] ?? '') ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['sales_person'] ?? '') ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['take_person'] ?? '') ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['brand_code'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['sku_code'] ?? '') ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['sku_name'] ?? '') ?></td>
                    <td class="text-right"><?= number_format($row['avg_monthly_last_year'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= number_format($row['min_qty_last_year'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= number_format($row['max_qty_last_year'] ?? 0, 2) ?></td>
                    <td><input type="text" class="form-control form-control-sm target-qty" value="<?= number_format($row['target_qty'] ?? 0, 2) ?>" style="width:80px;text-align:right;"></td>
                    <td class="text-right"><?= number_format($row['qty_month1'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= number_format($row['qty_month2'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= number_format($row['qty_month3'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= number_format($row['avg_3month'] ?? 0, 2) ?></td>
                    <td><input type="text" class="form-control form-control-sm forecast-qty" value="<?= number_format($row['forecast_qty'] ?? 0, 2) ?>" style="width:80px;text-align:right;"></td>
                    <td><input type="text" class="form-control form-control-sm compare-sales-1" value="<?= number_format($row['compare_sales_1'] ?? 0, 2) ?>" style="width:80px;text-align:right;"></td>
                    <td><input type="text" class="form-control form-control-sm compare-sales-2" value="<?= number_format($row['compare_sales_2'] ?? 0, 2) ?>" style="width:80px;text-align:right;"></td>
                    <td><input type="text" class="form-control form-control-sm remark" value="<?= htmlspecialchars($row['remark'] ?? '') ?>" style="width:150px;"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
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
                    <th>ยอดขายเดือน <?= $thai_months[$month1['month']].' '.$month1['year'] ?></th>
                    <th>ยอดขายเดือน <?= $thai_months[$month2['month']].' '.$month2['year'] ?></th>
                    <th>ยอดขายเดือน <?= $thai_months[$month3['month']].' '.$month3['year'] ?></th>
                    <th>ค่าเฉลี่ย 3 เดือน</th>
                    <th>คาดการณ์ยอดขาย <?php echo $thai_months[$selected_month] ?? ''; ?></th>
                    <th>เทียบเทียบคาดการณ์จำนวนเส้น กับ ค่าเฉลี่ย 3 เดือน</th>
                    <th>เทียบยอดขาย%จากค่าเฉลี่ย</th>
                    <th>หมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        </div>
        <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#salesTable').DataTable({
                "processing": true,
                "serverSide": false,
                "ajax": {
                    "url": "sales_data.php",
                    "data": function(d) {
                        d.month = $('#monthSelect').val();
                        d.year = $('#yearSelect').val();
                        d.ar_code = $('#arCode').val();
                    },
                    "dataSrc": function(json) {
                        // Only load data if arCode is not empty
                        if ($('#arCode').val() === '') {
                            return { data: [] };
                        }
                        return json.data;
                    }
                },
                "columns": [
                    { "data": 0 },   // รหัสร้านค้า
                    { "data": 1 },   // ชื่อร้านค้า
                    { "data": 17 },  // เซลส์
                    { "data": 18 },  // เทค
                    { "data": 2 },   // ยี่ห้อ
                    { "data": 3 },   // รหัสสินค้า
                    { "data": 4 },   // ชื่อสินค้า
                    { "data": 5 },   // ค่าเฉลี่ยต่อเดือน
                    { "data": 6 },   // ค่า MIN
                    { "data": 7 },   // ค่า MAX
                    { "data": 8, "render": function(data) { return '<input type="text" class="form-control form-control-sm target-qty" value="' + data + '" style="width:80px;text-align:right;">'; } },  // เป้ารายเดือน
                    { "data": 9 },   // ยอดขายเดือน3
                    { "data": 10 },  // ยอดขายเดือน2
                    { "data": 11 },  // ยอดขายเดือน1
                    { "data": 12 },  // ค่าเฉลี่ย 3 เดือน
                    { "data": 14, "render": function(data) { return '<input type="text" class="form-control form-control-sm compare-sales-1" value="' + data + '" style="width:80px;text-align:right;">'; } },  // เปรียบเทียบคาดการณ์จำนวนเส้น กับ ค่าเฉลี่ย 3 เดือน
                    { "data": 13, "render": function(data) { return '<input type="text" class="form-control form-control-sm forecast-qty" value="' + data + '" style="width:80px;text-align:right;">'; } },  // คาดการณ์ยอดขาย
                    { "data": 15, "render": function(data) { return '<input type="text" class="form-control form-control-sm compare-sales-2" value="' + data + '" style="width:80px;text-align:right;">'; } },  // เปรียบยอดขาย%จากค่าเฉลี่ย
                    { "data": 16, "render": function(data) { return '<input type="text" class="form-control form-control-sm remark" value="' + data + '" style="width:150px;">'; } }  // หมายเหตุ
                ],
                "lengthMenu": [[12, 30, 50, 100], [12, 30, 50, 100]],
                "pageLength": 12,
                dom: 'lfrtip',
                "language": {
                    "lengthMenu": "แสดง _MENU_ รายการ",
                    "zeroRecords": "ไม่พบข้อมูล",
                    "info": "แสดงหน้า _PAGE_ จาก _PAGES_",
                    "infoEmpty": "ไม่มีข้อมูล",
                    "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
                    "search": "ค้นห:",
                    "paginate": {
                        "first": "หน้าแรก",
                        "last": "หน้าสุดท้าย",
                        "next": "ถัดไป",
                        "previous": "ก่อนหน้า"
                    }
                }
            });

            $('#searchBtn').click(function() {
                var month = $('#monthSelect').val();
                var year = $('#yearSelect').val();
                var arCode = $('#arCode').val();
                var customerSearchVal = $('#customerSearch').val();

                // Check if all required fields are selected
                if (!customerSearchVal) {
                    alert('กรุณาเลือกร้านค้า');
                    return;
                }
                if (!arCode) {
                    alert('กรุณาเลือกร้านค้าจากรายการแนะนำ');
                    return;
                }
                if (!month || !year) {
                    alert('กรุณาเลือกเดือนและปีก่อนค้นหา');
                    return;
                }

                // Check if data already exists
                $.get('check_exists.php', {
                    ar_code: arCode,
                    month: month,
                    year: year
                }, function(checkResponse) {
                    if (checkResponse.exists) {
                        alert('มีการบันทึกข้อมูลนี้แล้ว กรุณาไปที่รายการแก้ไข');
                        // Clear the table
                        table.clear().draw();
                        if (confirm('ต้องการไปหน้าแก้ไขใช่หรือไม่?')) {
                            window.location.href = 'sales_report.php?load_id=' + checkResponse.header_id;
                        }
                        return;
                    } else {
                        // Not exists, proceed with search
                        table.ajax.reload();
                    }
                }, 'json');
            });

            $('#saveBtn').click(function() {
                var data = [];
                table.rows().every(function(rowIdx) {
                    var rowData = this.data().slice(); // copy array
                    // Update target_qty from input
                    var $target = $(this.node()).find('.target-qty');
                    if ($target.length) {
                        rowData[8] = $target.val(); // index 8 = target_qty
                    }
                    // Update forecast_qty from input
                    var $forecast = $(this.node()).find('.forecast-qty');
                    if ($forecast.length) {
                        rowData[13] = $forecast.val(); // index 13 = forecast_qty
                    }
                    // Update compare_sales_1 from input
                    var $compare1 = $(this.node()).find('.compare-sales-1');
                    if ($compare1.length) {
                        rowData[14] = $compare1.val(); // index 14 = compare_sales_1
                    }
                    // Update compare_sales_2 from input
                    var $compare2 = $(this.node()).find('.compare-sales-2');
                    if ($compare2.length) {
                        rowData[15] = $compare2.val(); // index 15 = compare_sales_2
                    }
                    // Update remark from input
                    var $remark = $(this.node()).find('.remark');
                    if ($remark.length) {
                        rowData[16] = $remark.val(); // index 16 = remark
                    }
                    data.push(rowData);
                });
                
                if (data.length === 0) {
                    alert('ไม่มีข้อมูลที่จะบันทึก กรุณาค้นหาข้อมูลก่อน');
                    return;
                }

                var arCode = $('#arCode').val();
                var arName = $('#customerSearch').val();
                var month = $('#monthSelect').val();
                var year = $('#yearSelect').val();

                // Check if already exists
                $.get('check_exists.php', {
                    ar_code: arCode,
                    month: month,
                    year: year
                }, function(checkResponse) {
                    if (checkResponse.exists) {
                        if (confirm('มีการบันทึกข้อมูลนี้แล้ว ต้องการไปหน้าแก้ไขใช่หรือไม่?')) {
                            window.location.href = 'sales_report.php?load_id=' + checkResponse.header_id;
                        }
                        return;
                    }

                    if (!confirm('ต้องการบันทึกข้อมูลทั้งหมด ' + data.length + ' รายการใช่หรือไม่?')) {
                        return;
                    }

                    $.ajax({
                        url: 'save_report.php',
                        type: 'POST',
                        data: {
                            ar_code: arCode,
                            ar_name: arName,
                            month: month,
                            year: year,
                            details: JSON.stringify(data)
                        },
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
                }, 'json');
            });

            $('#resetBtn').click(function() {
                $('#monthSelect').val(<?= date('n') ?>);
                $('#yearSelect').val(<?= date('Y') ?>);
                $('#customerSearch').val('');
                $('#arCode').val('');
                table.ajax.reload();
            });
        });

        // Auto-calculate เปรียบเทียบเทียบคาดการณ์จำนวนเส้น กับ ค่าเฉลี่ย 3 เดือน and เปรียบเทียบยอดขาย% when forecast_qty changes
        // avg_3month is already rounded in PHP (round up if decimal >= 0.5, round down if < 0.5)
        $('#salesTable tbody').on('input', '.forecast-qty', function() {
            var $row = $(this).closest('tr');
            var forecastVal = parseFloat($(this).val()) || 0;
            var avg3MonthText = $row.find('td').eq(14).text().replace(/,/g, ''); // column 14 in HTML = avg_3month (rounded)
            var avg3Month = parseFloat(avg3MonthText) || 0;
            var compare1 = forecastVal - avg3Month;
            $row.find('.compare-sales-1').val(compare1.toFixed(2));
            
                // Calculate เปรียบเทียบยอดขาย% = (forecast_qty - avg_3month) / avg_3month * 100
                var compare2 = (avg3Month !== 0) ? ((forecastVal - avg3Month) / avg3Month) * 100 : 0;
                $row.find('.compare-sales-2').val(compare2.toFixed(2));
        });

        const customers = <?= json_encode($customers ?? []) ?>;
        let selectedIndex = -1;
        
        $('#customerSearch').on('input', function() {
            const query = $(this).val().toLowerCase();
            const suggestionsDiv = $('#suggestions');
            suggestionsDiv.empty();
            selectedIndex = -1;
            
            if (query.length < 1) {
                suggestionsDiv.hide();
                return;
            }
            
            const filtered = customers.filter(c => c.f_name.toLowerCase().includes(query)).slice(0, 10);
            
            if (filtered.length === 0) {
                suggestionsDiv.hide();
                return;
            }
            
            filtered.forEach((c, idx) => {
                const $div = $('<div class="autocomplete-suggestion">').text(c.f_name).attr('data-idx', idx);
                $div.on('click', function() {
                    $('#customerSearch').val(c.f_name);
                    $('#arCode').val(c.customer_id);
                    suggestionsDiv.empty().hide();
                });
                suggestionsDiv.append($div);
            });
            
            suggestionsDiv.show();
        });
        
        $('#customerSearch').on('keydown', function(e) {
            const suggestionsDiv = $('#suggestions');
            const items = suggestionsDiv.find('.autocomplete-suggestion');
            
            if (items.length === 0) return;
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                items.removeClass('active').eq(selectedIndex).addClass('active');
                items.eq(selectedIndex)[0].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = selectedIndex <= 0 ? items.length - 1 : selectedIndex - 1;
                items.removeClass('active').eq(selectedIndex).addClass('active');
                items.eq(selectedIndex)[0].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                items.eq(selectedIndex).click();
            }
        });
        
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#customerSearch, #suggestions').length) {
                $('#suggestions').hide();
            }
        });
    </script>
</body>
</html>
<?php $conn = null; ?>
