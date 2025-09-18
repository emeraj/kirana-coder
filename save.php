<?php
// ตั้ง Timezone เป็นเวลาประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// รับค่าจากฟอร์ม
$user_code = $_POST['user_code'] ?? '';
$qr_data = $_POST['qr_data'] ?? '';

// ตรวจสอบว่า QR Code ถูกต้องหรือไม่
$part = "";
$remark = "";
$date = "";

if (strpos($qr_data, "!") !== false && strpos($qr_data, "#") !== false) {
    list($part, $temp) = explode("!", $qr_data, 2);
    list($remark, $dateAndRest) = explode("#", $temp, 2);
    $date = $dateAndRest;
}

// รวม Lot ทั้งหมดและเติมสถานะเริ่มต้น
$lots = [];
for ($i = 1; $i <= 32; $i++) {
    $key = "lot_$i";
    if (!empty($_POST[$key])) {
        $lots[] = trim($_POST[$key]) . ":ขายได้";
    }
}
$lot_data = implode(" ", $lots);

// ตรวจสอบข้อมูลซ้ำ
$check_stmt = $conn->prepare("SELECT id FROM pallet_data WHERE lot_data = ?");
$check_stmt->bind_param("s", $lot_data);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    // ถ้ามีข้อมูลซ้ำ ให้ถามยืนยัน
    echo "<script>
        if (confirm('ข้อมูลนี้มีอยู่แล้วในระบบ คุณต้องการบันทึกอีกครั้งหรือไม่?')) {
            window.location.href = 'index.php?user_code=" . urlencode($user_code) . "';
        } else {
            window.history.back();
        }
    </script>";
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

// เวลาปัจจุบัน
$created_at = date("Y-m-d H:i:s");

// บันทึกข้อมูลลงฐานข้อมูล
$insert_stmt = $conn->prepare("INSERT INTO pallet_data (user_code, part, remark, date, lot_data, created_at)
                               VALUES (?, ?, ?, ?, ?, ?)");
$insert_stmt->bind_param("ssssss", $user_code, $part, $remark, $date, $lot_data, $created_at);

if ($insert_stmt->execute()) {
    echo "<script>
        alert('✅ บันทึกข้อมูลเรียบร้อยแล้ว');
        window.location.href = 'index.php?user_code=" . urlencode($user_code) . "';
    </script>";
} else {
    echo "<script>
        alert('❌ เกิดข้อผิดพลาดในการบันทึก: " . addslashes($insert_stmt->error) . "');
        window.history.back();
    </script>";
}

$insert_stmt->close();
$conn->close();
?>
