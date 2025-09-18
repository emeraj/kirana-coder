<?php
date_default_timezone_set('Asia/Bangkok');

$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $part = trim($_POST["part"] ?? "");
    $remark = trim($_POST["remark"] ?? "");
    $qr_date = trim($_POST["qr_date"] ?? "");
    $bulk_lots_raw = trim($_POST["bulk_lots"] ?? "");
    $global_status = trim($_POST["global_status"] ?? "ขายได้");
    $lots = [];

    if ($bulk_lots_raw !== "") {
        $lines = preg_split("/[\r\n\s]+/", $bulk_lots_raw);
        $lines = array_filter($lines); // remove empty
        $lines = array_slice($lines, 0, 32); // limit 32
        foreach ($lines as $line) {
            $lot = trim($line);
            if ($lot !== "") {
                $lots[] = "$lot:$global_status";
            }
        }
    }

    if (empty($part)) {
        $error = "กรุณากรอก Part";
    } elseif (empty($lots)) {
        $error = "กรุณาวาง Lot อย่างน้อย 1 รายการ";
    } else {
        $lot_data = implode(" ", $lots);
        $created_at = date("Y-m-d H:i:s");
        $stmt = $conn->prepare("INSERT INTO pallet_data (user_code, part, remark, date, lot_data, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $user_code = "Manual";
        $stmt->bind_param("ssssss", $user_code, $part, $remark, $qr_date, $lot_data, $created_at);
        if ($stmt->execute()) {
            $success = "✅ บันทึกเรียบร้อยแล้ว จำนวน " . count($lots) . " Lots";
        } else {
            $error = "❌ เกิดข้อผิดพลาด: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มข้อมูลแบบ Manual</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-size: 14px;
        }
        .header { background-color: #343a40; color: #fff; padding: 10px; text-align: center; }
        .container { margin-top: 30px; }
        textarea.form-control { font-size: 13px; height: 200px; resize: vertical; }
        .nav-buttons { text-align: center; margin-bottom: 20px; }
        .nav-buttons .btn { margin: 0 4px; font-size: 13px; padding: 5px 12px; }
        .status-buttons .btn { margin: 3px; font-size: 12px; }
    </style>
</head>
<body>
<div class="header"><h4>Pallet Management System</h4></div>

<div class="container">
    <div class="nav-buttons">
        <button onclick="window.location.href='index.php'" class="btn btn-success">🏠 Home</button>
        <button onclick="window.location.href='search.php'" class="btn btn-warning text-dark">🔍 Search</button>
        <button onclick="window.location.href='sale.php'" class="btn btn-danger">✂️ Change Status</button>
        <button onclick="window.location.href='export_page.php'" class="btn btn-primary">📤 Export</button>
        <button onclick="window.location.href='removed_lot_log.php'" class="btn btn-dark">📜 Log</button>
        <button onclick="window.location.href='part_management.php'" class="btn btn-info">⚙️ Part Config</button>
    </div>

    <h4 class="text-center mb-4">📝 เพิ่มข้อมูล Pallet แบบ Manual (Copy จาก Excel)</h4>

    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>📦 Part</label>
            <input type="text" name="part" class="form-control" required>
        </div>
        <div class="form-group">
            <label>📝 Remark</label>
            <input type="text" name="remark" class="form-control">
        </div>
        <div class="form-group">
            <label>📅 Date</label>
            <input type="text" name="qr_date" class="form-control" placeholder="เช่น 30/07/25/A">
        </div>

        <div class="form-group">
            <label>📋 วาง Lot ที่ Copy จาก Excel (สูงสุด 32 รายการ)</label>
            <textarea name="bulk_lots" class="form-control" placeholder="วาง Lot แล้วระบบจะอ่านอัตโนมัติ"></textarea>
        </div>

        <div class="form-group">
            <label>⚙️ เลือกสถานะทั้งหมด:</label>
            <div class="status-buttons">
                <?php
                $statuses = ["ขายได้", "ขายแล้ว", "B/Confirm", "B/Rescreen", "ตัดเข้าLine", "CTI"];
                foreach ($statuses as $st) {
                    echo "<button type='button' class='btn btn-outline-secondary' onclick=\"setGlobalStatus('$st')\">$st</button>";
                }
                ?>
            </div>
            <input type="hidden" name="global_status" id="global_status" value="ขายได้">
            <input type="text" id="selected_status" class="form-control mt-2" value="ขายได้" readonly>
        </div>

        <button type="submit" class="btn btn-primary btn-block">💾 บันทึกข้อมูล</button>
    </form>
        <footer class="text-center text-muted mt-4">
        <hr>
        <p class="mb-1">&copy; <?php echo date("Y"); ?> Pallet Management System. All Rights Reserved.</p>
        <p>Developed by Jadsada Suphab (Dewer)</p>
    </footer>
</div>

<script>
function setGlobalStatus(status) {
    document.getElementById("global_status").value = status;
    document.getElementById("selected_status").value = status;
}
</script>
</body>
</html>
