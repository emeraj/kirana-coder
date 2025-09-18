<?php
require_once 'SimpleXLSXGen.php';

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลจากตาราง lot_removed_log
$sql = "SELECT * FROM lot_removed_log ORDER BY removed_at DESC";
$result = $conn->query($sql);

// เตรียมข้อมูลหัวตาราง
$data = [];
$data[] = ['ID', 'Lot Code', 'สถานะเดิม', 'รายละเอียด Case', 'เวลาที่ลบออก'];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            $row['id'],
            $row['lot_code'],
            $row['old_status'],
            $row['case_detail'],
            $row['removed_at']
        ];
    }
} else {
    echo "<h3 style='color:red; text-align:center;'>❌ ไม่มีข้อมูลให้ส่งออก</h3>";
    exit;
}

$conn->close();

// ส่งออกไฟล์ Excel
$filename = "removed_lot_log_" . date("Ymd_His") . ".xlsx";
SimpleXLSXGen::fromArray($data)->downloadAs($filename);
exit;
