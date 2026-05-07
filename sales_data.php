<?php
date_default_timezone_set("Asia/Bangkok");
include('config/connect_db.php');

$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
    4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
    10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selected_ar_code = isset($_GET['ar_code']) ? $_GET['ar_code'] : '';

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

$last_year = $selected_year - 1;

// Build sales query from ims_data_sale_sac_all
$sql = "SELECT
    s.AR_CODE,
    MAX(s.AR_NAME) as AR_NAME,
    s.SKU_CODE,
    s.SKU_NAME,
    s.BRAND,
    SUM(CASE WHEN s.DI_MONTH = ? AND s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE 0 END) as qty_month1,
    SUM(CASE WHEN s.DI_MONTH = ? AND s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE 0 END) as qty_month2,
    SUM(CASE WHEN s.DI_MONTH = ? AND s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE 0 END) as qty_month3,
    (SUM(CASE WHEN s.DI_MONTH = ? AND s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE 0 END) +
     SUM(CASE WHEN s.DI_MONTH = ? AND s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE 0 END) +
     SUM(CASE WHEN s.DI_MONTH = ? AND s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE 0 END)) / 3 as avg_3month,
    SUM(CASE WHEN s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE 0 END) / 12 as avg_monthly_last_year,
    MIN(CASE WHEN s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE NULL END) as min_qty_last_year,
    MAX(CASE WHEN s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE NULL END) as max_qty_last_year,
    MAX(s.DI_REF) as DI_REF
FROM ims_data_sale_sac_all s
WHERE s.AR_CODE LIKE 'SAC%'";

// Parameters for SELECT clause (15 params)
$params = array();
$params[] = $month1['month'];
$params[] = $month1['year'];
$params[] = $month2['month'];
$params[] = $month2['year'];
$params[] = $month3['month'];
$params[] = $month3['year'];
$params[] = $month1['month'];
$params[] = $month1['year'];
$params[] = $month2['month'];
$params[] = $month2['year'];
$params[] = $month3['month'];
$params[] = $month3['year'];
$params[] = $last_year;
$params[] = $last_year;
$params[] = $last_year;

// WHERE clause for AR_CODE
if ($selected_ar_code != '') {
    $sql .= " AND s.AR_CODE = ?";
    $params[] = $selected_ar_code;
}

$sql .= " GROUP BY s.AR_CODE, s.AR_NAME, s.SKU_CODE, s.SKU_NAME, s.BRAND
          HAVING (SUM(CASE WHEN s.DI_MONTH = ? AND s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE 0 END) +
                  SUM(CASE WHEN s.DI_MONTH = ? AND s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE 0 END) +
                  SUM(CASE WHEN s.DI_MONTH = ? AND s.DI_YEAR = ? THEN CAST(s.TRD_QTY AS DECIMAL(10,2)) ELSE 0 END)) > 0
          ORDER BY s.AR_CODE, s.SKU_CODE";

// Parameters for HAVING clause (6 params)
$params[] = $month1['month'];
$params[] = $month1['year'];
$params[] = $month2['month'];
$params[] = $month2['year'];
$params[] = $month3['month'];
$params[] = $month3['year'];

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get SALE_NAME and TAKE_NAME from ims_data_sale_sac_all
$di_refs = array_values(array_unique(array_column($results, 'DI_REF')));
$person_map = [];
if (!empty($di_refs)) {
    $placeholders = implode(',', array_fill(0, count($di_refs), '?'));
    $sql_person = "SELECT DI_REF, MAX(SALE_NAME) as SALE_NAME, MAX(TAKE_NAME) as TAKE_NAME
                   FROM ims_data_sale_sac_all
                   WHERE DI_REF IN ($placeholders)
                   GROUP BY DI_REF";
    $stmt_person = $conn->prepare($sql_person);
    $stmt_person->execute($di_refs);
    $persons = $stmt_person->fetchAll(PDO::FETCH_ASSOC);
    foreach ($persons as $p) {
        $person_map[$p['DI_REF']] = [
            'SALE_NAME' => $p['SALE_NAME'],
            'TAKE_NAME' => $p['TAKE_NAME']
        ];
    }
}

$data = [];
foreach ($results as $row) {
    $person = $person_map[$row['DI_REF']] ?? ['SALE_NAME' => '', 'TAKE_NAME' => ''];

    // Round avg_3month
    $avg_3month_raw = floatval($row['avg_3month'] ?? 0);
    $decimal = $avg_3month_raw - floor($avg_3month_raw);
    $avg_3month_rounded = ($decimal >= 0.5) ? ceil($avg_3month_raw) : floor($avg_3month_raw);

    $forecast_qty = 0;
    $compare_sales_1 = $forecast_qty - $avg_3month_rounded;

    $data[] = [
        $row['AR_CODE'],
        $row['AR_NAME'],
        $row['BRAND'],
        $row['SKU_CODE'],
        $row['SKU_NAME'],
        number_format($row['avg_monthly_last_year'] ?? 0, 2),
        number_format($row['min_qty_last_year'] ?? 0, 2),
        number_format($row['max_qty_last_year'] ?? 0, 2),
        '0.00',
        number_format($row['qty_month1'] ?? 0, 2),
        number_format($row['qty_month2'] ?? 0, 2),
        number_format($row['qty_month3'] ?? 0, 2),
        number_format($avg_3month_rounded, 2),
        '0.00',
        number_format($compare_sales_1, 2),
        '0.00',
        '',
        $person['SALE_NAME'],
        $person['TAKE_NAME']
    ];
}

echo json_encode(['data' => $data]);
$conn = null;
?>
