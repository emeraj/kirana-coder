<?php
date_default_timezone_set('Asia/Bangkok');

$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$success = "";
$error = "";

// สร้างตาราง bc_records ถ้ายังไม่มี
$create_table_sql = "CREATE TABLE IF NOT EXISTS bc_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lot_code VARCHAR(20) NOT NULL,
    status ENUM('B/Confirm', 'B/Rescreen') NOT NULL,
    case_detail TEXT,
    part_code VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cancelled_at TIMESTAMP NULL,
    is_cancelled BOOLEAN DEFAULT FALSE,
    INDEX idx_lot_code (lot_code),
    INDEX idx_status (status),
    INDEX idx_cancelled (is_cancelled)
)";
$conn->query($create_table_sql);

// จัดการ POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $lot_codes_raw = trim($_POST["lot_codes"] ?? "");
            $status = trim($_POST["status"] ?? "");
            $case_detail = trim($_POST["case_detail"] ?? "");
            $part_code = trim($_POST["part_code"] ?? "");

            if (empty($lot_codes_raw)) {
                $error = "กรุณาใส่ Lot Code";
            } elseif (empty($status)) {
                $error = "กรุณาเลือกสถานะ";
            } elseif (empty($case_detail)) {
                $error = "กรุณาใส่รายละเอียด Case";
            } else {
                // แยก Lot Codes
                $lot_lines = preg_split("/[\r\n]+/", $lot_codes_raw);
                $lot_codes = array_filter(array_map('trim', $lot_lines));
                
                $success_count = 0;
                $duplicate_count = 0;
                
                foreach ($lot_codes as $lot_code) {
                    if (strlen($lot_code) !== 10) continue; // ต้องครบ 10 ตัวอักษร
                    
                    // ตรวจสอบซ้ำ
                    $check_stmt = $conn->prepare("SELECT id FROM bc_records WHERE lot_code = ? AND is_cancelled = FALSE");
                    $check_stmt->bind_param("s", $lot_code);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $duplicate_count++;
                    } else {
                        $stmt = $conn->prepare("INSERT INTO bc_records (lot_code, status, case_detail, part_code) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssss", $lot_code, $status, $case_detail, $part_code);
                        if ($stmt->execute()) {
                            $success_count++;
                        }
                        $stmt->close();
                    }
                    $check_stmt->close();
                }
                
                if ($success_count > 0) {
                    $success = "✅ เพิ่มข้อมูล $success_count รายการเรียบร้อยแล้ว";
                    if ($duplicate_count > 0) {
                        $success .= " (พบซ้ำ $duplicate_count รายการ)";
                    }
                } else {
                    $error = "❌ ไม่สามารถเพิ่มข้อมูลได้ (อาจซ้ำหรือรูปแบบไม่ถูกต้อง)";
                }
            }
        } elseif ($_POST['action'] === 'cancel' && isset($_POST['record_id'])) {
            $record_id = (int)$_POST['record_id'];
            $stmt = $conn->prepare("UPDATE bc_records SET is_cancelled = TRUE, cancelled_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $record_id);
            
            if ($stmt->execute()) {
                $success = "✅ ยกเลิกข้อมูลเรียบร้อยแล้ว";
            } else {
                $error = "❌ เกิดข้อผิดพลาดในการยกเลิกข้อมูล";
            }
            $stmt->close();
        }
    }
}

// ดึงข้อมูล B/Confirm และ B/Rescreen ที่ยังไม่ถูกยกเลิก
$records_result = $conn->query("
    SELECT * FROM bc_records 
    WHERE is_cancelled = FALSE 
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการ B/Confirm & B/Rescreen</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-size: 14px;
        }
        .header { 
            background-color: #343a40; 
            color: #fff; 
            padding: 10px; 
            text-align: center; 
        }
        .container { 
            margin-top: 30px; 
        }
        .nav-buttons { 
            text-align: center; 
            margin-bottom: 20px; 
        }
        .nav-buttons .btn { 
            margin: 0 4px; 
            font-size: 13px; 
            padding: 5px 12px; 
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .status-confirm {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .status-rescreen {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        textarea {
            resize: vertical;
        }
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
        <button onclick="window.location.href='bc_management.php'" class="btn btn-secondary">🔧 B/C Management</button>
    </div>

    <h4 class="text-center mb-4">🔧 จัดการ B/Confirm และ B/Rescreen</h4>

    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <!-- ฟอร์มเพิ่มข้อมูล -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">➕ เพิ่มข้อมูล B/Confirm หรือ B/Rescreen</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>📋 Lot Codes (หนึ่ง lot ต่อบรรทัด)</label>
                            <textarea name="lot_codes" class="form-control" rows="8" 
                                     placeholder="ABC1234567&#10;DEF9876543&#10;XYZ1111111" required></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>📊 สถานะ</label>
                            <select name="status" class="form-control" required>
                                <option value="">-- เลือกสถานะ --</option>
                                <option value="B/Confirm">🟢 B/Confirm</option>
                                <option value="B/Rescreen">🟡 B/Rescreen</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>📦 Part Code</label>
                            <input type="text" name="part_code" class="form-control" 
                                   placeholder="เช่น J3007F" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label>📝 Case Detail</label>
                            <textarea name="case_detail" class="form-control" rows="4" 
                                     placeholder="รายละเอียดปัญหาหรือเหตุผล..." required></textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-sm">💾 เพิ่มข้อมูล</button>
            </form>
        </div>
    </div>

    <!-- ตารางแสดงข้อมูล -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">📋 รายการ B/Confirm และ B/Rescreen</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($records_result && $records_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Lot Code</th>
                                <th>สถานะ</th>
                                <th>Part Code</th>
                                <th>Case Detail</th>
                                <th>วันที่สร้าง</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $records_result->fetch_assoc()): ?>
                                <tr class="<?= $row['status'] == 'B/Confirm' ? 'status-confirm' : 'status-rescreen' ?>">
                                    <td><?= $row['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['lot_code']) ?></strong></td>
                                    <td>
                                        <?php if ($row['status'] == 'B/Confirm'): ?>
                                            <span class="badge badge-success">🟢 B/Confirm</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">🟡 B/Rescreen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['part_code']) ?></td>
                                    <td title="<?= htmlspecialchars($row['case_detail']) ?>">
                                        <?= htmlspecialchars(mb_substr($row['case_detail'], 0, 50)) ?>
                                        <?= mb_strlen($row['case_detail']) > 50 ? '...' : '' ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline-block;" 
                                              onsubmit="return confirm('คุณต้องการยกเลิกข้อมูลนี้หรือไม่?')">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">❌ ยกเลิก</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center p-4">
                    <p class="text-muted">ยังไม่มีข้อมูล B/Confirm หรือ B/Rescreen</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- คำอธิบายการใช้งาน -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">ℹ️ คำอธิบายการใช้งาน</h5>
        </div>
        <div class="card-body">
            <h6>การจัดการ B/Confirm และ B/Rescreen:</h6>
            <ul>
                <li><strong>B/Confirm:</strong> Lot ที่ต้องการยืนยันความถูกต้อง</li>
                <li><strong>B/Rescreen:</strong> Lot ที่ต้องตรวจสอบซ้ำ</li>
                <li><strong>Case Detail:</strong> ระบุรายละเอียดปัญหาหรือเหตุผล</li>
                <li><strong>การยกเลิก:</strong> สามารถยกเลิกสถานะได้ (ไม่ลบข้อมูล แต่ทำเครื่องหมายเป็น cancelled)</li>
            </ul>
            <p><strong>หมายเหตุ:</strong> ข้อมูลจะถูกนำไปใช้ในการ Export และรายงานต่างๆ</p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>