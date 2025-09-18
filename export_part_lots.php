<?php
require_once 'SimpleXLSXGen.php';

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$selected_part = $_GET['selected_part'] ?? '';
$selected_part = trim($selected_part);

if (empty($selected_part)) {
    die("<h3 style='color:red; text-align:center;'>❌ กรุณาเลือก Part เช่น D3022A</h3>");
}

// ดึงข้อมูล part + lot_data + date
$stmt = $conn->prepare("SELECT lot_data, date FROM pallet_data WHERE part = ?");
$stmt->bind_param("s", $selected_part);
$stmt->execute();
$result = $stmt->get_result();

$lots = [];
$lot_seen = []; // เก็บจำนวน lot เพื่อตรวจว่าซ้ำ
while ($row = $result->fetch_assoc()) {
    $lot_items = explode(" ", $row['lot_data']);
    $real_date = $row['date'];
    foreach ($lot_items as $item) {
        $parts = explode(":", $item);
        if (count($parts) >= 2) {
            $lot_code = trim($parts[0]);
            $status = trim($parts[1]);

            // ข้ามสถานะที่ไม่ต้องการ
            if ($status !== 'ขายแล้ว' && $status !== 'ตัดเข้าLine') {
                // ตรวจว่าซ้ำ
                if (isset($lot_seen[$lot_code])) {
                    $lot_seen[$lot_code]++;
                    $display_lot_code = $lot_code . " (ซ้ำ)";
                } else {
                    $lot_seen[$lot_code] = 1;
                    $display_lot_code = $lot_code;
                }

                $lots[] = [$display_lot_code, $real_date];
            }
        }
    }
}

$conn->close();

// เตรียม Excel
$data = [['No.', 'Lot Code', 'Part', 'Date']];
foreach ($lots as $i => $lot_info) {
    [$lot_code, $real_date] = $lot_info;
    $data[] = [$i + 1, $lot_code, $selected_part, $real_date];
}

if (count($data) === 1) {
    echo "<h3 style='color:red; text-align:center;'>❌ ไม่พบ Lot สำหรับ Part ที่เลือก</h3>";
    exit;
}

// สร้างไฟล์ Excel
$filename = "export_part_{$selected_part}_" . date("Ymd_His") . ".xlsx";
SimpleXLSXGen::fromArray($data)->downloadAs($filename);
exit;
?>
