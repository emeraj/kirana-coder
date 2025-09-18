<?php
$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึง distinct part
$parts = [];
$result = $conn->query("SELECT DISTINCT part FROM pallet_data WHERE part <> ''");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $parts[] = $row['part'];
    }
}
// เรียงตามตัวอักษรแบบ natural (เช่น P1, P2, P10)
natsort($parts);
$parts = array_values($parts);

// ดึง remark และ date แยกตาม part
$remarks_by_part = [];
$dates_by_part = [];
foreach ($parts as $p) {
    $remarks = [];
    $dates = [];

    $escaped_p = $conn->real_escape_string($p);

    $r_result = $conn->query("SELECT DISTINCT remark FROM pallet_data WHERE part='$escaped_p' AND remark <> ''");
    if ($r_result) {
        while ($r = $r_result->fetch_assoc()) {
            $remarks[] = $r['remark'];
        }
    }
    natsort($remarks);
    $remarks_by_part[$p] = array_values($remarks);

    $d_result = $conn->query("SELECT DISTINCT date FROM pallet_data WHERE part='$escaped_p' AND date <> ''");
    if ($d_result) {
        while ($d = $d_result->fetch_assoc()) {
            $dates[] = $d['date'];
        }
    }
    natsort($dates);
    $dates_by_part[$p] = array_values($dates);
}

// รับค่าการค้นหา
$selected_part = $_GET['part'] ?? "";
$selected_remark = $_GET['remark'] ?? "";
$selected_date = $_GET['date'] ?? "";
$keyword = $_GET['keyword'] ?? "";

// เงื่อนไข
$where_clauses = [];
if ($keyword != "") {
    $k = $conn->real_escape_string($keyword);
    $where_clauses[] = "(part LIKE '%$k%' OR date LIKE '%$k%' OR lot_data LIKE '%$k%' OR remark LIKE '%$k%')";
}
if ($selected_part != "") {
    $sp = $conn->real_escape_string($selected_part);
    $where_clauses[] = "part = '$sp'";
}
if ($selected_date != "") {
    $sd = $conn->real_escape_string($selected_date);
    $where_clauses[] = "date = '$sd'";
}
if ($selected_remark != "") {
    $sr = $conn->real_escape_string($selected_remark);
    $where_clauses[] = "remark = '$sr'";
}
$where = "";
if (count($where_clauses) > 0) {
    $where = "WHERE " . implode(" AND ", $where_clauses);
}

$sql = "SELECT * FROM pallet_data $where ORDER BY id DESC";
$search_result = $conn->query($sql);
function highlightKeyword($text, $keyword) {
    if (empty($keyword)) return $text;
    return preg_replace("/(" . preg_quote($keyword, '/') . ")/i", "<span style='background-color: #ffff00; font-weight: bold;'>$1</span>", $text);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pallet Management - Search</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h1 class="text-center mb-4">Search Data</h1>
        <form action="search.php" method="GET" class="mb-4">
          <div class="form-group text-center">
            <label><strong>Keyword:</strong></label>
            <input type="text" name="keyword" class="form-control w-75 mx-auto" value="<?= htmlspecialchars($keyword) ?>">
          </div>
          <div class="form-group text-center">
            <label><strong>Part:</strong></label>
            <select name="part" id="part" class="form-control w-75 mx-auto">
              <option value="">-- All Parts --</option>
              <?php foreach ($parts as $p): ?>
              <option value="<?= $p ?>" <?= $selected_part == $p ? "selected" : "" ?>><?= $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group text-center">
            <label><strong>Remark:</strong></label>
            <select name="remark" id="remark" class="form-control w-75 mx-auto">
              <option value="">-- All Remarks --</option>
              <?php
              if ($selected_part && isset($remarks_by_part[$selected_part])) {
                foreach ($remarks_by_part[$selected_part] as $r) {
                  echo "<option value=\"$r\"" . ($r == $selected_remark ? " selected" : "") . ">$r</option>";
                }
              }
              ?>
            </select>
          </div>
          <div class="form-group text-center">
            <label><strong>Date:</strong></label>
            <select name="date" id="date" class="form-control w-75 mx-auto">
              <option value="">-- All Dates --</option>
              <?php
              if ($selected_part && isset($dates_by_part[$selected_part])) {
                foreach ($dates_by_part[$selected_part] as $d) {
                  echo "<option value=\"$d\"" . ($d == $selected_date ? " selected" : "") . ">$d</option>";
                }
              }
              ?>
            </select>
          </div>
          <div class="text-center">
            <button type="submit" class="btn btn-success">Search</button>
          </div>
        </form>

        <?php
        if ($search_result && $search_result->num_rows > 0) {
          echo '<div class="table-scrollable"><table class="table table-bordered">';
          echo '<thead class="thead-dark"><tr>
                  <th>ID</th><th>User</th><th>Part</th><th>Remark</th><th>Date</th><th>Timestamp</th><th>Action</th>
                </tr></thead><tbody>';
          while ($row = $search_result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['user_code']}</td>
                    <td>{$row['part']}</td>
                    <td>{$row['remark']}</td>
                    <td>{$row['date']}</td>
                    <td>{$row['created_at']}</td>
                    <td><a href='edit.php?id={$row['id']}' class='btn btn-sm btn-outline-success'>Edit</a></td>
                  </tr>";
            $lot_data = explode(" ", $row['lot_data']);
          echo "<tr><td colspan='7'><div class='row'>";
echo "<div class='col-md-6 border-right pr-3'>";
for ($i = 0; $i < 16; $i++) {
    if (isset($lot_data[$i])) {
        echo "<p>Box " . ($i + 1) . ": " . highlightKeyword($lot_data[$i], $keyword) . "</p>";
    }
}
echo "</div>";
echo "<div class='col-md-6 pl-3'>";
for ($i = 16; $i < 32; $i++) {
    if (isset($lot_data[$i])) {
        echo "<p>Box " . ($i + 1) . ": " . highlightKeyword($lot_data[$i], $keyword) . "</p>";
    }
}
echo "</div></div></td></tr>";

          }
          echo '</tbody></table></div>';
        } else {
          echo "<p class='text-center text-muted'>No results found.</p>";
        }
        ?>
        <div class="text-center mt-3">
          <a href="index.php" class="btn btn-primary">Back to Home</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script>
    const remarksByPart = <?= json_encode($remarks_by_part); ?>;
    const datesByPart = <?= json_encode($dates_by_part); ?>;

    $('#part').on('change', function () {
      const part = $(this).val();

      // Update remark
      let remark = $('#remark');
      remark.empty().append('<option value="">-- All Remarks --</option>');
      if (remarksByPart[part]) {
        [...remarksByPart[part]].sort().forEach(val => {
          remark.append(<option value="${val}">${val}</option>);
        });
      }

      // Update date
      let date = $('#date');
      date.empty().append('<option value="">-- All Dates --</option>');
      if (datesByPart[part]) {
        [...datesByPart[part]].sort().forEach(val => {
          date.append(<option value="${val}">${val}</option>);
        });
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