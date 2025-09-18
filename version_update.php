<?php
$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

date_default_timezone_set("Asia/Bangkok"); // ตั้งเวลาไทย
$message = "";

// ดึงเวอร์ชันล่าสุด
$latest_version = "";
$latest_query = $conn->query("SELECT version FROM version_updates ORDER BY updated_at DESC LIMIT 1");
if ($latest_query && $latest_query->num_rows > 0) {
    $latest_version = $latest_query->fetch_assoc()['version'];
}

// ถ้ามีการส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $version = trim($_POST['version'] ?? "");
    $details = trim($_POST['details'] ?? "");
    $updated_by = trim($_POST['updated_by'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if ($password !== "myinternet") {
        $message = "<div class='alert alert-danger'>❌ รหัสผ่านไม่ถูกต้อง</div>";
    } elseif ($version === "") {
        $message = "<div class='alert alert-warning'>⚠️ กรุณาใส่ชื่อเวอร์ชัน</div>";
    } elseif ($updated_by === "") {
        $message = "<div class='alert alert-warning'>⚠️ กรุณาใส่ชื่อผู้พัฒนา</div>";
    } else {
        $now = date("Y-m-d H:i:s");
        $stmt = $conn->prepare("INSERT INTO version_updates (version, details, updated_by, updated_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $version, $details, $updated_by, $now);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ เพิ่มเวอร์ชันใหม่เรียบร้อย: <strong>$version</strong></div>";
            $latest_version = $version;
        } else {
            $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการเพิ่มเวอร์ชัน</div>";
        }
        $stmt->close();
    }
}

// ดึงรายการเวอร์ชันย้อนหลัง
$versions = [];
$res = $conn->query("SELECT version, details, updated_by, updated_at FROM version_updates ORDER BY updated_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $versions[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Update Version</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: sans-serif; font-size: 15px; }
        .container { max-width: 700px; margin-top: 40px; }
        .version-history { margin-top: 30px; }
        .form-control { font-size: 14px; }
        textarea.form-control { resize: vertical; }
    </style>
        <style>
    body {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    }
    .header {
      background-color: #343a40;
      padding: 15px;
      color: #fff;
      text-align: center;
    }
    .nav-buttons {
      margin: 20px 0;
      text-align: center;
    }
    .nav-buttons .btn {
      margin: 0 5px;
    }
    .table-scrollable {
      max-height: 600px;
      overflow-y: auto;
      display: block;
    }
    .table-scrollable table {
      width: 100%;
    }
    .table-scrollable thead, .table-scrollable tbody tr {
      display: table;
      width: 100%;
      table-layout: fixed;
    }
    .table-scrollable tbody {
      display: block;
      overflow-y: auto;
    }
  </style>
</head>
<body>
        <header class="header">
    <h2>Pallet Management System</h2>
  </header>

  <div class="container mt-4">
    <div class="nav-buttons">
      <button onclick="window.location.href='index.php'" class="btn btn-success">🏠 Home</button>
      <button onclick="window.location.href='search.php'" class="btn btn-warning text-dark">🔍 Search</button>
      <button onclick="window.location.href='sale.php'" class="btn btn-danger">✂️ Change Status</button>
      <button onclick="window.location.href='export_page.php'" class="btn btn-primary">📤 Export</button>
      <button onclick="window.location.href='removed_lot_log.php'" class="btn btn-dark">📜 Log</button>
      <button onclick="window.location.href='part_management.php'" class="btn btn-info">⚙️ Part Config</button>
    </div>
<div class="container">
    <h4 class="mb-3 text-center">📌 อัปเดตเวอร์ชันระบบ</h4>

    <div class="alert alert-info text-center">
        🔖 เวอร์ชันล่าสุด: <strong><?= htmlspecialchars($latest_version ?: 'ยังไม่มีเวอร์ชัน') ?></strong>
    </div>

    <?= $message ?>

    <form method="POST" class="mb-4">
        <div class="form-group">
            <label for="version">ชื่อเวอร์ชัน (เช่น v1.2.3 หรือ 2025-06-19):</label>
            <input type="text" class="form-control" name="version" id="version" required>
        </div>
        <div class="form-group">
            <label for="details">รายละเอียดของเวอร์ชันนี้:</label>
            <textarea class="form-control" name="details" id="details" rows="3" placeholder="เช่น แก้ไข bug เพิ่มปุ่ม ลบข้อมูลได้ ฯลฯ"></textarea>
        </div>
        <div class="form-group">
            <label for="updated_by">ชื่อผู้พัฒนา:</label>
            <input type="text" class="form-control" name="updated_by" id="updated_by" placeholder="เช่น Jadsada" required>
        </div>
        <div class="form-group">
            <label for="password">รหัสผ่านเพื่ออัปเดต:</label>
            <input type="password" class="form-control" name="password" id="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">💾 บันทึกเวอร์ชัน</button>
    </form>

    <div class="version-history">
        <h5 class="mb-3">📜 ประวัติเวอร์ชันที่ผ่านมา</h5>
        <ul class="list-group">
            <?php foreach ($versions as $v): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <strong><?= htmlspecialchars($v['version']) ?></strong>
                        <span class="badge badge-secondary"><?= htmlspecialchars($v['updated_at']) ?></span>
                    </div>
                    <div><strong>ผู้พัฒนา:</strong> <?= htmlspecialchars($v['updated_by']) ?></div>
                    <small class="text-muted"><?= nl2br(htmlspecialchars($v['details'])) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-outline-dark">🔙 กลับหน้าแรก</a>
    </div>
</div>
          <footer class="text-center text-muted mt-4">
        <hr>
        <p class="mb-1">&copy; <?php echo date("Y"); ?> Pallet Management System. All Rights Reserved.</p>
        <p>Developed by Jadsada Suphab (Dewer)</p>
    </footer>
</body>
</html>
