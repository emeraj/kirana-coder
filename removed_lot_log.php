<?php
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลจากตาราง log
$sql = "SELECT * FROM lot_removed_log ORDER BY removed_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Lot Removed Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }
        .header {
            background-color: #343a40;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .container {
            margin-top: 30px;
        }
        .nav-buttons {
            margin: 20px 0;
            text-align: center;
        }
        .nav-buttons .btn {
            margin: 0 5px;
        }
        .scroll-table {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>📦 รายการ Lot ที่ถูกลบ (ตัดเข้าLine)</h2>
    </div>

    <div class="container">
        <div class="nav-buttons">
            <button onclick="window.location.href='index.php'" class="btn btn-success">🏠 Home</button>
            <button onclick="window.location.href='search.php'" class="btn btn-warning text-dark">🔍 Search</button>
            <button onclick="window.location.href='sale.php'" class="btn btn-danger">✂️ Change Status</button>
            <button onclick="window.location.href='export_page.php'" class="btn btn-primary">📤 Export</button>
            <button onclick="window.location.href='removed_lot_log.php'" class="btn btn-dark">📜 Log</button>
            <button onclick="window.location.href='part_management.php'" class="btn btn-info">⚙️ Part Config</button>
        </div>
            <button onclick="window.location.href='export_removed_log.php'" class="btn btn-outline-success">📥 Export Excel</button>


        <div class="card shadow">
            <div class="card-body">
                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive scroll-table">
                        <table class="table table-bordered table-striped text-center align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Lot Code</th>
                                    <th>สถานะเดิม</th>
                                    <th>รายละเอียด Case</th>
                                    <th>เวลาที่ลบออก</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td><?= htmlspecialchars($row['lot_code']) ?></td>
                                        <td><?= htmlspecialchars($row['old_status']) ?></td>
                                        <td><?= htmlspecialchars($row['case_detail']) ?></td>
                                        <td><?= htmlspecialchars($row['removed_at']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">ยังไม่มีรายการที่ถูกลบ</div>
                <?php endif; ?>
            </div>
        </div>
            <footer class="text-center text-muted mt-4">
        <hr>
        <p class="mb-1">&copy; <?php echo date("Y"); ?> Pallet Management System. All Rights Reserved.</p>
        <p>Developed by Jadsada Suphab (Dewer)</p>
    </footer>
    </div>
</body>
</html>
