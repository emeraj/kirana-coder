<?php
require_once 'SimpleXLSXGen.php';

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// รับกลุ่มสถานะจากฟอร์ม
$status_group = $_GET['status_group'] ?? '';

if ($status_group === 'stock') {
    $selected_statuses = ['ขายได้', 'B/Confirm', 'B/Rescreen'];
} elseif ($status_group === 'sold') {
    $selected_statuses = ['ขายแล้ว'];
} else {
    die("<h3 style='color:red; text-align:center;'>❌ กรุณาเลือกกลุ่มสถานะที่ต้องการ</h3>");
}

// ดึงข้อมูลจากฐานข้อมูล
$sql = "SELECT part, remark, date, lot_data FROM pallet_data";
$result = $conn->query($sql);

// เตรียมข้อมูล
$rows = [];
$current_date = date('d/m/Y');

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $part = trim($row['part']);
        $remark = trim($row['remark']);
        $date_raw = trim($row['date']); // เช่น 16/05/25
        $lot_data = $row['lot_data'];

        $lot_items = explode(" ", $lot_data);
        $count = 0;

        foreach ($lot_items as $item) {
            $parts = explode(":", $item);
            if (count($parts) < 2) continue;

            $status = $parts[1];
            if (in_array($status, $selected_statuses)) {
                $count++;
            }
        }

        if ($count > 0) {
            $qr_code = "{$part}!{$remark}#{$date_raw}";

            // แปลงวันที่ (dd/mm/yy) → yy-mm-dd เพื่อใช้สำหรับ sorting
            $date_parts = explode("/", $date_raw);
            if (count($date_parts) >= 3) {
                $sortable_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
            } else {
                $sortable_date = "0000-00-00"; // fallback
            }

            $rows[] = [
                'export_date' => $current_date,
                'qr_code' => $qr_code,
                'box_count' => $count,
                'sort_date' => $sortable_date // ใช้เรียงลำดับ
            ];
        }
    }
}

$conn->close();

// ตรวจสอบ
if (count($rows) == 0) {
    echo "<h3 style='color:red; text-align:center;'>❌ ไม่มีข้อมูลสำหรับกลุ่มสถานะที่เลือก</h3>";
    exit;
}

// เรียงตามวันที่ (ปี/เดือน/วัน)
usort($rows, function($a, $b) {
    return strcmp($a['sort_date'], $b['sort_date']);
});

// เตรียมข้อมูลสำหรับ export
$data = [];
$data[] = ['DATE', 'QR Code', 'จำนวน BOX'];
foreach ($rows as $r) {
    $data[] = [$r['export_date'], $r['qr_code'], $r['box_count']];
}

// ส่งออกเป็น Excel
$filename = "export_lots_" . $status_group . "_" . date("Ymd_His") . ".xlsx";
SimpleXLSXGen::fromArray($data)->downloadAs($filename);
exit;
?>
