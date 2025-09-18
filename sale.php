<?php
date_default_timezone_set('Asia/Bangkok'); // ตั้งเวลาประเทศไทย

$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success_message = "";
$error = "";
$lot_input_display = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lot_input = $_POST['lot_codes'] ?? "";
    $sale_date = $_POST['sale_date'] ?? "";
    $sale_status = $_POST['sale_status'] ?? "";
    $case_detail = trim($_POST['case_detail'] ?? "");
    $lot_input_display = htmlspecialchars($lot_input);

    $lot_codes = array_filter(array_map('trim', preg_split("/[\r\n]+/", $lot_input)));

    if (empty($lot_codes)) {
        $error = "กรุณาใส่ lot code อย่างน้อย 1 รายการ";
    } elseif (empty($sale_date)) {
        $error = "กรุณาเลือกวันที่ขาย";
    } elseif (empty($sale_status)) {
        $error = "กรุณาเลือกสถานะ";
    } elseif (mb_strtolower($sale_status) === "ตัดเข้าline" && $case_detail === "") {
        $error = "กรุณาระบุ Case ก่อนตัดเข้า Line";
    } else {
        $updated_lots = [];
        $removed_lots = [];
        $blocked_lots = [];

        $select_sql = "SELECT id, lot_data FROM pallet_data";
        $result = $conn->query($select_sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $record_id = $row['id'];
                $lot_data_str = trim($row['lot_data']);
                $lot_items = preg_split('/\s+/', $lot_data_str);
                $new_lot_items = [];
                $changed = false;

                foreach ($lot_items as $item) {
                    $parts = explode(":", $item);
                    $code = $parts[0] ?? '';
                    $old_status = $parts[1] ?? '';
                    $old_date = $parts[2] ?? '';

                    if (in_array($code, $lot_codes)) {
                        if (mb_strtolower($sale_status) === 'ขายแล้ว' && in_array(mb_strtolower($old_status), ['b/confirm', 'b/rescreen'])) {
                            $blocked_lots[] = "$code (สถานะเดิม: $old_status)";
                            $new_lot_items[] = $item;
                            continue;
                        }

                        if (mb_strtolower($sale_status) === "ตัดเข้าline") {
                            $stmt = $conn->prepare("INSERT INTO lot_removed_log (lot_code, old_status, case_detail) VALUES (?, ?, ?)");
                            $stmt->bind_param("sss", $code, $old_status, $case_detail);
                            $stmt->execute();
                            $stmt->close();

                            $removed_lots[] = $code;
                            $changed = true;
                            continue; // ลบออกจาก lot_data
                        }

                        $new_item = "$code:$sale_status:$sale_date";
                        $new_lot_items[] = $new_item;
                        $updated_lots[] = $code;
                        $changed = true;
                    } else {
                        $new_lot_items[] = $item;
                    }
                }

                if ($changed) {
                    $new_lot_data = implode(" ", $new_lot_items);
                    $update_sql = "UPDATE pallet_data SET lot_data = '" . $conn->real_escape_string($new_lot_data) . "' WHERE id = $record_id";
                    $conn->query($update_sql);
                }
            }
        }

        if (!empty($updated_lots)) {
            $success_message .= "✅ อัปเดตสถานะเป็น '$sale_status':<br>" . implode(", ", $updated_lots) . "<br>";
        }
        if (!empty($removed_lots)) {
            $success_message .= "🗑 ลบออกจากระบบ (ตัดเข้าLine):<br>" . implode(", ", $removed_lots) . "<br>";
        }
        if (!empty($blocked_lots)) {
            $error = "❌ ไม่สามารถเปลี่ยนเป็น 'ขายแล้ว' ได้ (สถานะเดิมคือ B/Confirm หรือ B/Rescreen):<br><strong>" . implode(", ", $blocked_lots) . "</strong>";
        }

        // ล้างช่อง Lot Code ถ้าทำสำเร็จ
        if (!empty($updated_lots) || !empty($removed_lots)) {
            $lot_input_display = "";
        }
    }
}

$default_date = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <title>Pallet Management - Update Sale Status</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
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
      text-align: center;
      margin: 20px 0;
    }
    .nav-buttons .btn {
      margin: 0 5px;
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const statusSelect = document.getElementById('sale_status');
      const caseInputDiv = document.getElementById('case_input_group');

      function toggleCaseInput() {
        caseInputDiv.style.display = statusSelect.value === 'ตัดเข้าLine' ? 'block' : 'none';
      }

      statusSelect.addEventListener('change', toggleCaseInput);
      toggleCaseInput();
    });
  </script>
</head>
<body>
  <div class="header">
    <h2>Pallet Management System</h2>
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

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h3 class="card-title text-center mb-4">Update Sale Status</h3>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
          <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <form method="POST" action="sale.php">
          <div class="form-group">
            <label for="lot_codes">Paste Lot Codes from Excel (หนึ่ง lot ต่อบรรทัด):</label>
            <textarea name="lot_codes" id="lot_codes" rows="8" class="form-control" placeholder="LotCode1&#10;LotCode2&#10;LotCode3"><?= $lot_input_display ?></textarea>
          </div>
          <div class="form-group">
            <label for="sale_status">สถานะ:</label>
            <select name="sale_status" id="sale_status" class="form-control" required>
              <option value="">-- เลือกสถานะ --</option>
              <?php
                $statuses = ['ขายแล้ว', 'ขายได้', 'B/Confirm', 'B/Rescreen', 'ตัดเข้าLine'];
                foreach ($statuses as $status) {
                    $selected = ($sale_status === $status) ? 'selected' : '';
                    echo "<option value=\"$status\" $selected>$status</option>";
                }
              ?>
            </select>
          </div>
          <div class="form-group" id="case_input_group" style="display:none;">
            <label for="case_detail">Case (ระบุเหตุผล):</label>
            <input type="text" name="case_detail" id="case_detail" class="form-control" placeholder="เช่น ซ่อมบอร์ด, Rework ฯลฯ" value="<?= htmlspecialchars($_POST['case_detail'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label for="sale_date">Sale Date:</label>
            <input type="date" name="sale_date" id="sale_date" class="form-control" required value="<?= htmlspecialchars($_POST['sale_date'] ?? $default_date) ?>" />
          </div>
          <button type="submit" class="btn btn-success btn-block">✅ Confirm</button>
        </form>
      </div>
    </div>
  </div>
        <footer class="text-center text-muted mt-4">
        <hr>
        <p class="mb-1">&copy; <?php echo date("Y"); ?> Pallet Management System. All Rights Reserved.</p>
        <p>Developed by Jadsada Suphab (Dewer)</p>
    </footer>
</body>
</html>
