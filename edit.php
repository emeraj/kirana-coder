<?php
$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) die("Invalid request.");
$id = $conn->real_escape_string($_GET['id']);

$sql = "SELECT * FROM pallet_data WHERE id = '$id'";
$result = $conn->query($sql);
if ($result->num_rows <= 0) die("Record not found.");

$row = $result->fetch_assoc();
$lot_data_array = explode(" ", $row['lot_data']);
$lots = [];

for ($i = 0; $i < 32; $i++) {
    if (isset($lot_data_array[$i])) {
        $parts = explode(":", $lot_data_array[$i]);
        $lotCode = $parts[0];
        $status = $parts[1] ?? "";
        $sale_date = $parts[2] ?? "";
        $case = "";

        if (str_contains($sale_date, "(Case:")) {
            preg_match('/(.*?)\(Case:(.*?)\)/', $sale_date, $matches);
            $sale_date = trim($matches[1] ?? "");
            $case = trim($matches[2] ?? "");
        }
    } else {
        $lotCode = "";
        $status = "ขายได้";
        $sale_date = "";
        $case = "";
    }
    $lots[$i] = ['code' => $lotCode, 'status' => $status, 'sale_date' => $sale_date, 'case' => $case];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $part = $conn->real_escape_string($_POST['part'] ?? '');
    $remark = $conn->real_escape_string($_POST['remark'] ?? '');
    $date = $conn->real_escape_string($_POST['qr_date'] ?? '');
    $global_case = trim($_POST["global_case"] ?? "");

    $new_lots = [];
    for ($i = 1; $i <= 32; $i++) {
        $lotCode = trim($_POST["lot_$i"] ?? "");
        $status = trim($_POST["status_$i"] ?? "");
        $sale_date = trim($_POST["sale_date_$i"] ?? "");
        $case = ($status === "ตัดเข้าLine") ? $global_case : "";

        if ($lotCode !== "") {
            $lot_string = "$lotCode:$status:$sale_date";
            if ($status === 'ตัดเข้าLine' && $case !== "") {
                $lot_string .= "(Case: $case)";
            }
            $new_lots[] = $conn->real_escape_string($lot_string);
        }
    }

    $new_lot_data = implode(" ", $new_lots);
    $update_sql = "UPDATE pallet_data SET part = '$part', remark = '$remark', date = '$date', lot_data = '$new_lot_data' WHERE id = '$id'";

    if ($conn->query($update_sql) === TRUE) {
        // แสดง popup แล้ว redirect
        echo "<script>
                alert('✅ บันทึกข้อมูลเรียบร้อยแล้ว');
                window.location.href = 'search.php';
              </script>";
        exit;
    } else {
        echo "<div class='container mt-5'><div class='alert alert-danger'>Error updating record: " . $conn->error . "</div></div>";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Edit Lot Data</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .btn-status { min-width: 110px; }
        .nav-buttons { margin: 20px 0; text-align: center; }
        .nav-buttons .btn { margin: 0 5px; }
    </style>
</head>
<body>
<header class="bg-dark text-white text-center py-3">
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

    <h3 class="text-center">Edit Lot Data for Record ID <?= htmlspecialchars($id) ?></h3>

    <form method="POST" id="editForm">
        <div class="form-group">
            <label>📦 Part</label>
            <input type="text" class="form-control" name="part" value="<?= htmlspecialchars($row['part']) ?>" required>
        </div>
        <div class="form-group">
            <label>📌 Remark</label>
            <input type="text" class="form-control" name="remark" value="<?= htmlspecialchars($row['remark']) ?>">
        </div>
        <div class="form-group">
            <label>🗓️ QR Date</label>
            <input type="text" class="form-control" name="qr_date" value="<?= htmlspecialchars($row['date']) ?>">
        </div>

        <div class="form-group mt-4">
            <label><strong>ตั้งค่าสถานะทั้งหมด:</strong></label>
            <div class="d-flex flex-wrap">
                <?php foreach (["ขายได้", "ขายแล้ว", "B/Confirm", "B/Rescreen", "ตัดเข้าLine", "CTI"] as $status): ?>
                    <button type="button" class="btn btn-outline-secondary btn-status m-1" onclick="setAllStatus('<?= $status ?>')"><?= $status ?></button>
                <?php endforeach; ?>
            </div>

            <label class="mt-3">Sale Date (เฉพาะสำหรับ "ขายแล้ว"):</label>
            <input type="date" id="global_sale_date" class="form-control" />

            <label class="mt-3">Global Case (สำหรับ "ตัดเข้าLine"):</label>
            <input type="text" id="global_case" class="form-control" placeholder="ระบุสาเหตุ เช่น NG, Oversize">
            <input type="hidden" name="global_case" id="global_case_hidden">
        </div>

        <div class="row">
            <?php foreach ($lots as $i => $lot): 
                $index = $i + 1;
            ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Lot <?= $index ?></h5>
                        <div class="form-group">
                            <label>Lot Code</label>
                            <input type="text" class="form-control" name="lot_<?= $index ?>" value="<?= htmlspecialchars($lot['code']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="status_<?= $index ?>" id="status_<?= $index ?>">
                                <?php
                                $statuses = ["ขายได้", "ขายแล้ว", "B/Confirm", "B/Rescreen", "ตัดเข้าLine", "CTI"];
                                foreach ($statuses as $status) {
                                    $selected = ($status == $lot['status']) ? "selected" : "";
                                    echo "<option value=\"$status\" $selected>$status</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sale Date</label>
                            <input type="date" class="form-control" name="sale_date_<?= $index ?>" id="sale_date_<?= $index ?>" value="<?= htmlspecialchars($lot['sale_date']) ?>">
                        </div>
                        <input type="hidden" class="form-control case-input" name="case_<?= $index ?>" id="case_<?= $index ?>" value="<?= htmlspecialchars($lot['case']) ?>">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-success btn-block my-4">💾 Update Lot Data</button>
    </form>
</div>

<script>
function setAllStatus(status) {
    const globalDate = document.getElementById('global_sale_date').value;
    const globalCase = document.getElementById('global_case').value;

    for (let i = 1; i <= 32; i++) {
        const select = document.getElementById('status_' + i);
        const saleDate = document.getElementById('sale_date_' + i);
        const caseInput = document.getElementById('case_' + i);

        if (select) select.value = status;

        if (status === 'ขายแล้ว' && globalDate && saleDate) {
            saleDate.value = globalDate;
        }

        if (caseInput) {
            if (status === 'ตัดเข้าLine') {
                caseInput.value = globalCase;
            } else {
                caseInput.value = '';
            }
        }
    }
}

document.getElementById('editForm').addEventListener('submit', function () {
    document.getElementById('global_case_hidden').value = document.getElementById('global_case').value;
});

document.addEventListener('DOMContentLoaded', function () {
    for (let i = 1; i <= 32; i++) {
        const select = document.getElementById('status_' + i);
        const caseInput = document.getElementById('case_' + i);
        if (select && caseInput) {
            select.addEventListener('change', function () {
                if (this.value === 'ตัดเข้าLine') {
                    caseInput.value = document.getElementById('global_case').value;
                } else {
                    caseInput.value = '';
                }
            });
        }
    }
        
});
        
</script>
        <footer class="text-center text-muted mt-4">
        <hr>
        <p class="mb-1">&copy; <?php echo date("Y"); ?> Pallet Management System. All Rights Reserved.</p>
        <p>Developed by Jadsada Suphab (Dewer)</p>
    </footer>
</body>
</html>
