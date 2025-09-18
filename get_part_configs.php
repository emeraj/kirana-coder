<?php
header('Content-Type: application/json');

$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// ดึงข้อมูล Part Configuration ทั้งหมด
$sql = "SELECT part_code, customer_short, customer_order FROM part_config";
$result = $conn->query($sql);

$configs = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $configs[$row['part_code']] = [
            'customer_short' => $row['customer_short'],
            'customer_order' => $row['customer_order']
        ];
    }
}

$conn->close();
echo json_encode($configs);
?>