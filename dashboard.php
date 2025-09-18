<?php
date_default_timezone_set('Asia/Bangkok');

$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- 1. Lot ที่เพิ่มในแต่ละวัน (7 วันล่าสุด) ---
$daily_data = [];
$daily_query = "
    SELECT DATE(created_at) as day, COUNT(*) as count
    FROM pallet_data
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(created_at)
    ORDER BY day ASC
";
$res = $conn->query($daily_query);
while ($row = $res->fetch_assoc()) {
    $daily_data[$row['day']] = $row['count'];
}

// --- 2. สถานะของ lot ล่าสุดทั้งหมด ---
$status_data = [];
$statuses = ['ขายได้', 'ขายแล้ว', 'B/Confirm', 'B/Rescreen', 'ตัดเข้าLine'];
foreach ($statuses as $status) {
    $status_data[$status] = 0;
}

$status_query = "SELECT lot_data FROM pallet_data";
$res = $conn->query($status_query);
while ($row = $res->fetch_assoc()) {
    $lots = preg_split('/\s+/', trim($row['lot_data']));
    foreach ($lots as $lot) {
        $parts = explode(':', $lot);
        if (count($parts) >= 2) {
            $status = $parts[1];
            if (isset($status_data[$status])) {
                $status_data[$status]++;
            }
        }
    }
}

// --- 3. ตัดเข้า Line รายเดือน (12 เดือนล่าสุด) ---
$cti_data = [];
$cti_query = "
    SELECT DATE_FORMAT(removed_at, '%Y-%m') AS month, COUNT(*) AS count
    FROM lot_removed_log
    GROUP BY month
    ORDER BY month ASC
";
$res = $conn->query($cti_query);
while ($row = $res->fetch_assoc()) {
    $cti_data[$row['month']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Pallet Management</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
  <div class="container my-5">
    <h2 class="mb-4 text-center">📊 Dashboard - Pallet Management</h2>

    <!-- กราฟ: เพิ่มแต่ละวัน -->
    <div class="mb-5">
      <h4>จำนวน Lot ที่เพิ่มแต่ละวัน (7 วันล่าสุด)</h4>
      <canvas id="dailyChart" height="100"></canvas>
    </div>

    <!-- กราฟ: สถานะทั้งหมด -->
    <div class="mb-5">
      <h4>ปริมาณสินค้าแยกตามสถานะ</h4>
      <canvas id="statusChart" height="100"></canvas>
    </div>

    <!-- กราฟ: ตัดเข้า Line -->
    <div class="mb-5">
      <h4>จำนวน Lot ที่ตัดเข้า Line แล้ว (รายเดือน)</h4>
      <canvas id="ctiChart" height="100"></canvas>
    </div>
  </div>

<script>
  // กราฟรายวัน
  const dailyCtx = document.getElementById('dailyChart').getContext('2d');
  new Chart(dailyCtx, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_keys($daily_data)) ?>,
      datasets: [{
        label: 'จำนวนที่เพิ่ม',
        data: <?= json_encode(array_values($daily_data)) ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.7)',
      }]
    }
  });

  // กราฟสถานะ
  const statusCtx = document.getElementById('statusChart').getContext('2d');
  new Chart(statusCtx, {
    type: 'pie',
    data: {
      labels: <?= json_encode(array_keys($status_data)) ?>,
      datasets: [{
        data: <?= json_encode(array_values($status_data)) ?>,
        backgroundColor: ['#28a745', '#007bff', '#ffc107', '#dc3545', '#6c757d'],
      }]
    }
  });

  // กราฟตัดเข้า Line รายเดือน
  const ctiCtx = document.getElementById('ctiChart').getContext('2d');
  new Chart(ctiCtx, {
    type: 'line',
    data: {
      labels: <?= json_encode(array_keys($cti_data)) ?>,
      datasets: [{
        label: 'จำนวนที่ตัดเข้า Line',
        data: <?= json_encode(array_values($cti_data)) ?>,
        borderColor: '#dc3545',
        backgroundColor: 'rgba(220, 53, 69, 0.2)',
        fill: true,
      }]
    }
  });
</script>
</body>
</html>
