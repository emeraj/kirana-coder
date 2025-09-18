<?php
// ดึงรายการ Part จากฐานข้อมูล
$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$parts = [];
$result = $conn->query("SELECT DISTINCT part FROM pallet_data WHERE part <> '' ORDER BY part ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $parts[] = $row['part'];
    }
}

// ดึงข้อมูลการเปลี่ยนสถานะเป็น "ขายแล้ว"
$sales_data = [];
$sales_result = $conn->query("
    SELECT 
        part,
        lot_data,
        created_at
    FROM pallet_data 
    WHERE lot_data LIKE '%:ขายแล้ว:%' 
    ORDER BY created_at DESC
");

if ($sales_result) {
    while ($row = $sales_result->fetch_assoc()) {
        $lot_items = explode(' ', $row['lot_data']);
        $sold_lots = [];
        
        foreach ($lot_items as $lot_item) {
            if (strpos($lot_item, ':ขายแล้ว:') !== false) {
                $parts_lot = explode(':', $lot_item);
                if (count($parts_lot) >= 3) {
                    $sold_lots[] = [
                        'lot_code' => $parts_lot[0],
                        'sale_date' => $parts_lot[2]
                    ];
                }
            }
        }
        
        foreach ($sold_lots as $sold_lot) {
            $sale_date = $sold_lot['sale_date'];
            if (!isset($sales_data[$sale_date])) {
                $sales_data[$sale_date] = [];
            }
            if (!isset($sales_data[$sale_date][$row['part']])) {
                $sales_data[$sale_date][$row['part']] = 0;
            }
            $sales_data[$sale_date][$row['part']]++;
        }
    }
}

// ดึงข้อมูล B/Confirm และ B/Rescreen
$bc_data = [];
$bc_result = $conn->query("
    SELECT 
        part_code,
        status,
        case_detail,
        DATE(created_at) as date,
        COUNT(*) as lot_count
    FROM bc_records 
    WHERE is_cancelled = FALSE 
    GROUP BY part_code, status, case_detail, DATE(created_at)
    ORDER BY DATE(created_at) DESC, part_code ASC
");

if ($bc_result) {
    while ($row = $bc_result->fetch_assoc()) {
        $date = $row['date'];
        if (!isset($bc_data[$date])) {
            $bc_data[$date] = [];
        }
        $bc_data[$date][] = $row;
    }
}

// เรียงวันที่จากใหม่ไปเก่า
krsort($sales_data);

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Export Pallet Lot Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .card {
            margin-bottom: 30px;
        }
        .sales-table {
            max-height: 500px;
            overflow-y: auto;
        }
        .date-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            font-weight: bold;
        }
        .part-row {
            background: #f8f9fa;
        }
        .total-row {
            background: #e3f2fd;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Pallet Management System</h2>
    </div>

    <div class="container">
        <!-- ปุ่มนำทาง -->
        <div class="nav-buttons">
            <button onclick="window.location.href='index.php'" class="btn btn-success">🏠 Home</button>
            <button onclick="window.location.href='search.php'" class="btn btn-warning text-dark">🔍 Search</button>
            <button onclick="window.location.href='sale.php'" class="btn btn-danger">✂️ Chang Status</button>
            <button onclick="window.location.href='export_page.php'" class="btn btn-primary">📤 Export</button>
            <button onclick="window.location.href='removed_lot_log.php'" class="btn btn-dark">📜 Log</button>
        </div>

        <!-- ตารางแสดงการเปลี่ยนสถานะ "ขายแล้ว" -->
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-success text-white text-center">
                <h3>📊 รายงานการเปลี่ยนสถานะ "ขายแล้ว" ประจำวัน</h3>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($sales_data)): ?>
                    <div class="sales-table">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th>📅 วันที่</th>
                                    <th>📦 Part</th>
                                    <th>🔢 จำนวน Lot</th>
                                    <th>📈 รวมต่อวัน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_data as $date => $parts_data): ?>
                                    <?php 
                                    $daily_total = array_sum($parts_data);
                                    $part_count = count($parts_data);
                                    $first_row = true;
                                    ?>
                                    
                                    <?php foreach ($parts_data as $part => $lot_count): ?>
                                        <tr class="<?= $first_row ? 'date-header' : 'part-row' ?>">
                                            <?php if ($first_row): ?>
                                                <td rowspan="<?= $part_count + 1 ?>" class="date-header text-center">
                                                    <strong><?= htmlspecialchars($date) ?></strong><br>
                                                    <small><?= date('D', strtotime($date)) ?></small>
                                                </td>
                                            <?php endif; ?>
                                            
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($part) ?></span></td>
                                            <td><span class="badge bg-success"><?= $lot_count ?> Lots</span></td>
                                            
                                            <?php if ($first_row): ?>
                                                <td rowspan="<?= $part_count + 1 ?>" class="text-center date-header">
                                                    <span class="badge bg-warning text-dark fs-6">
                                                        <?= $daily_total ?> Lots
                                                    </span>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php $first_row = false; ?>
                                    <?php endforeach; ?>
                                    
                                    <!-- แถวสรุปรายวัน -->
                                    <tr class="total-row">
                                        <td colspan="2" class="text-end"><strong>📋 สรุปวันที่ <?= htmlspecialchars($date) ?>:</strong></td>
                                        <td><strong><?= $part_count ?> Parts, รวม <?= $daily_total ?> Lots</strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- สถิติรวม -->
                    <div class="mt-3 p-3 bg-light">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <h5 class="text-primary">📅 ทั้งหมด</h5>
                                <span class="badge bg-primary fs-6"><?= count($sales_data) ?> วัน</span>
                            </div>
                            <div class="col-md-4">
                                <h5 class="text-success">📦 Parts ที่ขาย</h5>
                                <?php 
                                $all_parts = [];
                                foreach ($sales_data as $parts_data) {
                                    $all_parts = array_merge($all_parts, array_keys($parts_data));
                                }
                                $unique_parts = array_unique($all_parts);
                                ?>
                                <span class="badge bg-success fs-6"><?= count($unique_parts) ?> Parts</span>
                            </div>
                            <div class="col-md-4">
                                <h5 class="text-warning">🔢 Lots รวมทั้งหมด</h5>
                                <?php 
                                $total_lots = 0;
                                foreach ($sales_data as $parts_data) {
                                    $total_lots += array_sum($parts_data);
                                }
                                ?>
                                <span class="badge bg-warning text-dark fs-6"><?= $total_lots ?> Lots</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center p-5">
                        <h4 class="text-muted">📊 ยังไม่มีข้อมูลการขาย</h4>
                        <p class="text-muted">เมื่อมีการเปลี่ยนสถานะเป็น "ขายแล้ว" จะแสดงที่นี่</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ตารางแสดง B/Confirm และ B/Rescreen -->
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-warning text-dark text-center">
                <h3>🔧 รายงาน B/Confirm และ B/Rescreen ประจำวัน</h3>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($bc_data)): ?>
                    <div class="sales-table">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-warning sticky-top">
                                <tr>
                                    <th>📅 วันที่</th>
                                    <th>📦 Part</th>
                                    <th>📊 สถานะ</th>
                                    <th>📝 Case Detail</th>
                                    <th>🔢 จำนวน Lot</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bc_data as $date => $records): ?>
                                    <?php foreach ($records as $index => $record): ?>
                                        <tr class="<?= $index === 0 ? 'table-light' : '' ?>">
                                            <?php if ($index === 0): ?>
                                                <td rowspan="<?= count($records) ?>" class="text-center align-middle bg-warning">
                                                    <strong><?= htmlspecialchars($date) ?></strong><br>
                                                    <small><?= date('D', strtotime($date)) ?></small>
                                                </td>
                                            <?php endif; ?>
                                            
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($record['part_code']) ?></span></td>
                                            <td>
                                                <?php if ($record['status'] == 'B/Confirm'): ?>
                                                    <span class="badge bg-success">🟢 B/Confirm</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">🟡 B/Rescreen</span>
                                                <?php endif; ?>
                                            </td>
                                            <td title="<?= htmlspecialchars($record['case_detail']) ?>">
                                                <?= htmlspecialchars(mb_substr($record['case_detail'], 0, 30)) ?>
                                                <?= mb_strlen($record['case_detail']) > 30 ? '...' : '' ?>
                                            </td>
                                            <td><span class="badge bg-info"><?= $record['lot_count'] ?> Lots</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- สถิติรวม B/Confirm และ B/Rescreen -->
                    <div class="mt-3 p-3 bg-light">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h6 class="text-success">🟢 B/Confirm</h6>
                                <?php 
                                $confirm_total = 0;
                                foreach ($bc_data as $records) {
                                    foreach ($records as $record) {
                                        if ($record['status'] == 'B/Confirm') {
                                            $confirm_total += $record['lot_count'];
                                        }
                                    }
                                }
                                ?>
                                <span class="badge bg-success fs-6"><?= $confirm_total ?> Lots</span>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-warning">🟡 B/Rescreen</h6>
                                <?php 
                                $rescreen_total = 0;
                                foreach ($bc_data as $records) {
                                    foreach ($records as $record) {
                                        if ($record['status'] == 'B/Rescreen') {
                                            $rescreen_total += $record['lot_count'];
                                        }
                                    }
                                }
                                ?>
                                <span class="badge bg-warning text-dark fs-6"><?= $rescreen_total ?> Lots</span>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-info">📦 Parts ทั้งหมด</h6>
                                <?php 
                                $all_parts_bc = [];
                                foreach ($bc_data as $records) {
                                    foreach ($records as $record) {
                                        $all_parts_bc[] = $record['part_code'];
                                    }
                                }
                                $unique_parts_bc = array_unique($all_parts_bc);
                                ?>
                                <span class="badge bg-info fs-6"><?= count($unique_parts_bc) ?> Parts</span>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-primary">🔢 รวมทั้งหมด</h6>
                                <span class="badge bg-primary fs-6"><?= $confirm_total + $rescreen_total ?> Lots</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center p-5">
                        <h4 class="text-muted">🔧 ยังไม่มีข้อมูล B/Confirm และ B/Rescreen</h4>
                        <p class="text-muted">เมื่อมีการใส่ข้อมูลใน B/C Management จะแสดงที่นี่</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h3>📤 Export ตามกลุ่มสถานะ</h3>
            </div>
            <div class="card-body">
                <form action="export_unsold_lots.php" method="get">
                    <label class="form-label">📌 เลือกกลุ่มสถานะ:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status_group" value="stock" id="status_stock" required>
                        <label class="form-check-label" for="status_stock">ยอดสโต (ขายได้, B/Confirm, B/Rescreen)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status_group" value="sold" id="status_sold" required>
                        <label class="form-check-label" for="status_sold">ขายแล้ว</label>
                    </div>
                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-success">🔽 Export เป็น Excel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Export ตาม Part -->
        <div class="card shadow-lg mt-4">
            <div class="card-header bg-info text-white text-center">
                <h3>📤 Export ตาม Part</h3>
            </div>
            <div class="card-body">
                <form action="export_part_lots.php" method="get">
                    <div class="form-group">
                        <label for="selected_part">📦 เลือก Part:</label>
                        <select name="selected_part" id="selected_part" class="form-control" required>
                            <option value="">-- กรุณาเลือก Part --</option>
                            <?php foreach ($parts as $part): ?>
                                <option value="<?= htmlspecialchars($part) ?>"><?= htmlspecialchars($part) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mt-3 text-center">
                        <button type="submit" class="btn btn-info">🔽 Export Lot ของ Part</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>