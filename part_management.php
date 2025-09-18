<?php
date_default_timezone_set('Asia/Bangkok');

$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$success = "";
$error = "";

// สร้างตาราง part_config ถ้ายังไม่มี
$create_table_sql = "CREATE TABLE IF NOT EXISTS part_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_code VARCHAR(50) NOT NULL UNIQUE,
    customer_short VARCHAR(10) NOT NULL,
    customer_order VARCHAR(10) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// จัดการ POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $part_code = trim($_POST["part_code"] ?? "");
            $customer_short = trim($_POST["customer_short"] ?? "");
            $customer_order = trim($_POST["customer_order"] ?? "");
            $description = trim($_POST["description"] ?? "");

            if (empty($part_code)) {
                $error = "กรุณาใส่ Part Code";
            } elseif (empty($customer_short)) {
                $error = "กรุณาเลือกตัวย่อลูกค้า";
            } elseif (empty($customer_order)) {
                $error = "กรุณาใส่ลำดับลูกค้า";
            } else {
                // ตรวจสอบข้อมูลซ้ำ
                $check_stmt = $conn->prepare("SELECT id FROM part_config WHERE part_code = ? OR (customer_short = ? AND customer_order = ?)");
                $check_stmt->bind_param("sss", $part_code, $customer_short, $customer_order);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $existing = $check_result->fetch_assoc();
                    $error = "ข้อมูลซ้ำ: Part Code '$part_code' หรือ ตัวย่อลูกค้า '$customer_short' + ลำดับ '$customer_order' มีอยู่แล้ว";
                } else {
                    $stmt = $conn->prepare("INSERT INTO part_config (part_code, customer_short, customer_order, description) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $part_code, $customer_short, $customer_order, $description);
                    
                    if ($stmt->execute()) {
                        $success = "✅ เพิ่มข้อมูล Part เรียบร้อยแล้ว";
                    } else {
                        $error = "❌ เกิดข้อผิดพลาด: " . $stmt->error;
                    }
                    $stmt->close();
                }
                $check_stmt->close();
            }
        } elseif ($_POST['action'] === 'edit') {
            $edit_id = (int)$_POST['edit_id'];
            $part_code = trim($_POST["part_code"] ?? "");
            $customer_short = trim($_POST["customer_short"] ?? "");
            $customer_order = trim($_POST["customer_order"] ?? "");
            $description = trim($_POST["description"] ?? "");

            if (empty($part_code)) {
                $error = "กรุณาใส่ Part Code";
            } elseif (empty($customer_short)) {
                $error = "กรุณาเลือกตัวย่อลูกค้า";
            } elseif (empty($customer_order)) {
                $error = "กรุณาใส่ลำดับลูกค้า";
            } else {
                // ตรวจสอบข้อมูลซ้ำ (ยกเว้นตัวเอง)
                $check_stmt = $conn->prepare("SELECT id FROM part_config WHERE (part_code = ? OR (customer_short = ? AND customer_order = ?)) AND id != ?");
                $check_stmt->bind_param("sssi", $part_code, $customer_short, $customer_order, $edit_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $error = "ข้อมูลซ้ำ: Part Code '$part_code' หรือ ตัวย่อลูกค้า '$customer_short' + ลำดับ '$customer_order' มีอยู่แล้ว";
                } else {
                    $stmt = $conn->prepare("UPDATE part_config SET part_code = ?, customer_short = ?, customer_order = ?, description = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $part_code, $customer_short, $customer_order, $description, $edit_id);
                    
                    if ($stmt->execute()) {
                        $success = "✅ แก้ไขข้อมูล Part เรียบร้อยแล้ว";
                    } else {
                        $error = "❌ เกิดข้อผิดพลาดในการแก้ไข";
                    }
                    $stmt->close();
                }
                $check_stmt->close();
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['delete_id'])) {
            $delete_id = (int)$_POST['delete_id'];
            $stmt = $conn->prepare("DELETE FROM part_config WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            
            if ($stmt->execute()) {
                $success = "✅ ลบข้อมูลเรียบร้อยแล้ว";
            } else {
                $error = "❌ เกิดข้อผิดพลาดในการลบข้อมูล";
            }
            $stmt->close();
        }
    }
}

// ดึงข้อมูล Part ทั้งหมด จัดเรียงตาม customer_short และ part_code
$parts_result = $conn->query("SELECT * FROM part_config ORDER BY customer_short ASC, part_code ASC");
$parts_by_customer = [];
if ($parts_result && $parts_result->num_rows > 0) {
    while ($row = $parts_result->fetch_assoc()) {
        $parts_by_customer[$row['customer_short']][] = $row;
    }
}

// รายการตัวย่อลูกค้าตามลำดับที่กำหนด
$customer_order = ["ST", "WD", "HT", "TB", "SS"];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการข้อมูล Part</title>
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
        .form-control-sm {
            font-size: 13px;
        }
        .customer-section {
            border: 2px solid #007bff;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #f8f9ff;
        }
        .customer-header {
            background: #007bff;
            color: white;
            padding: 8px 15px;
            margin: -1px -1px 10px -1px;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
        }
        .edit-form {
            display: none;
            background: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .btn-edit {
            margin-right: 5px;
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
    </div>

    <h4 class="text-center mb-4">⚙️ จัดการข้อมูล Part Configuration</h4>

    <!-- ฟอร์มเพิ่มข้อมูล Part -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">➕ เพิ่มข้อมูล Part ใหม่</h5>
        </div>
        <div class="card-body">
            <form method="POST" onsubmit="return validateForm()">
                <input type="hidden" name="action" value="add">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>📦 Part Code</label>
                            <input type="text" name="part_code" class="form-control form-control-sm" 
                                   placeholder="เช่น K3003C" required maxlength="50">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>🏢 ตัวย่อลูกค้า (Digit 2-3)</label>
                            <select name="customer_short" class="form-control form-control-sm" required>
                                <option value="">-- เลือกตัวย่อลูกค้า --</option>
                                <option value="ST">ST</option>
                                <option value="WD">WD</option>
                                <option value="HT">HT</option>
                                <option value="TB">TB</option>
                                <option value="SS">SS</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>🔢 ลำดับลูกค้า (Digit 6)</label>
                            <input type="text" name="customer_order" class="form-control form-control-sm" 
                                   placeholder="เช่น 2" required maxlength="10">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>📝 คำอธิบาย (ไม่บังคับ)</label>
                            <input type="text" name="description" class="form-control form-control-sm" 
                                   placeholder="รายละเอียดเพิ่มเติม" maxlength="255">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-sm">💾 เพิ่มข้อมูล</button>
            </form>
        </div>
    </div>

    <!-- แสดงรายการ Part Configuration แยกตามตัวย่อลูกค้า -->
    <?php foreach ($customer_order as $customer): ?>
        <?php if (isset($parts_by_customer[$customer]) && count($parts_by_customer[$customer]) > 0): ?>
            <div class="customer-section">
                <div class="customer-header">
                    🏢 ตัวย่อลูกค้า: <?= $customer ?> (<?= count($parts_by_customer[$customer]) ?> รายการ)
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>Part Code</th>
                                <th>ลำดับลูกค้า</th>
                                <th>คำอธิบาย</th>
                                <th>วันที่สร้าง</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parts_by_customer[$customer] as $row): ?>
                                <tr id="row-<?= $row['id'] ?>">
                                    <td><strong><?= htmlspecialchars($row['part_code']) ?></strong></td>
                                    <td><span class="badge badge-warning"><?= htmlspecialchars($row['customer_order']) ?></span></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                onclick="showEditForm(<?= $row['id'] ?>)">✏️ แก้ไข</button>
                                        <form method="POST" style="display: inline-block;" 
                                              onsubmit="return confirm('คุณต้องการลบข้อมูลนี้หรือไม่?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️ ลบ</button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- ฟอร์มแก้ไข -->
                                <tr id="edit-<?= $row['id'] ?>" class="edit-form">
                                    <td colspan="5">
                                        <form method="POST" onsubmit="return validateEditForm(<?= $row['id'] ?>)">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label>Part Code:</label>
                                                    <input type="text" name="part_code" class="form-control form-control-sm" 
                                                           value="<?= htmlspecialchars($row['part_code']) ?>" required maxlength="50">
                                                </div>
                                                <div class="col-md-2">
                                                    <label>ตัวย่อลูกค้า:</label>
                                                    <select name="customer_short" class="form-control form-control-sm" required>
                                                        <option value="ST" <?= $row['customer_short'] == 'ST' ? 'selected' : '' ?>>ST</option>
                                                        <option value="WD" <?= $row['customer_short'] == 'WD' ? 'selected' : '' ?>>WD</option>
                                                        <option value="HT" <?= $row['customer_short'] == 'HT' ? 'selected' : '' ?>>HT</option>
                                                        <option value="TB" <?= $row['customer_short'] == 'TB' ? 'selected' : '' ?>>TB</option>
                                                        <option value="SS" <?= $row['customer_short'] == 'SS' ? 'selected' : '' ?>>SS</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label>ลำดับลูกค้า:</label>
                                                    <input type="text" name="customer_order" class="form-control form-control-sm" 
                                                           value="<?= htmlspecialchars($row['customer_order']) ?>" required maxlength="10">
                                                </div>
                                                <div class="col-md-3">
                                                    <label>คำอธิบาย:</label>
                                                    <input type="text" name="description" class="form-control form-control-sm" 
                                                           value="<?= htmlspecialchars($row['description']) ?>" maxlength="255">
                                                </div>
                                                <div class="col-md-2">
                                                    <label>&nbsp;</label><br>
                                                    <button type="submit" class="btn btn-success btn-sm">💾 บันทึก</button>
                                                    <button type="button" class="btn btn-secondary btn-sm" 
                                                            onclick="hideEditForm(<?= $row['id'] ?>)">❌ ยกเลิก</button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (empty($parts_by_customer)): ?>
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted">ยังไม่มีข้อมูล Part Configuration</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- คำอธิบายการใช้งาน -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">ℹ️ คำอธิบายการใช้งาน</h5>
        </div>
        <div class="card-body">
            <h6>การตรวจสอบ Lot Code:</h6>
            <ul>
                <li><strong>Digit ที่ 2-3:</strong> จะต้องตรงกับ "ตัวย่อลูกค้า" ที่กำหนดไว้</li>
                <li><strong>Digit ที่ 6:</strong> จะต้องตรงกับ "ลำดับลูกค้า" ที่กำหนดไว้</li>
                <li><strong>ตัวอย่าง:</strong> หาก Part K3003C มี ตัวย่อลูกค้า = SS และ ลำดับลูกค้า = 2</li>
                <li>Lot Code <code>QSSZ942001</code> → Digit 2-3 = SS ✅, Digit 6 = 4 ❌</li>
                <li>Lot Code <code>QSSZ922001</code> → Digit 2-3 = SS ✅, Digit 6 = 2 ✅</li>
            </ul>
            <p><strong>หมายเหตุ:</strong> ระบบจะตรวจสอบอัตโนมัติเมื่อบันทึกข้อมูลในหน้า Save Data และแสดงข้อผิดพลาดเป็นสีแดง</p>
        </div>
    </div>
</div>

<script>
// ฟังก์ชันแสดงฟอร์มแก้ไข
function showEditForm(id) {
    document.getElementById('edit-' + id).style.display = 'table-row';
}

// ฟังก์ชันซ่อนฟอร์มแก้ไข
function hideEditForm(id) {
    document.getElementById('edit-' + id).style.display = 'none';
}

// Validate form เพิ่มข้อมูล
function validateForm() {
    return true; // PHP จะจัดการ validation
}

// Validate form แก้ไขข้อมูล
function validateEditForm(id) {
    return true; // PHP จะจัดการ validation
}

// แสดง Alert สำหรับ Error
<?php if ($error): ?>
    alert('❌ <?= addslashes($error) ?>');
<?php elseif ($success): ?>
    alert('✅ <?= addslashes($success) ?>');
<?php endif; ?>
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>