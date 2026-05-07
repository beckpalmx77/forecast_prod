<?php
date_default_timezone_set("Asia/Bangkok");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode($_POST['data'] ?? '[]', true);
    $filename = $_POST['filename'] ?? 'export';
    
    if (!is_array($data) || empty($data)) {
        die('No data to export');
    }
    
    $filename = preg_replace('/[^a-zA-Z0-9\-\_\.ก-ฮ]/u', '_', $filename);
    $excel_file = $filename . '.xls';
    
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $excel_file . '"');
    header('Cache-Control: max-age=0');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body><table border="1">';
    
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table></body></html>';
    exit;
}
die('Invalid request');
