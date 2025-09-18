<?php
// ดึงเวอร์ชันล่าสุดจากฐานข้อมูล
$conn = new mysqli("fdb1030.awardspace.net", "4632313_root", "myinternet5", "4632313_root");
$latest_version = "N/A";
if (!$conn->connect_error) {
    $res = $conn->query("SELECT version FROM version_updates ORDER BY updated_at DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $latest_version = htmlspecialchars($row['version']);
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pallet Management - Save Data</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      font-size: 14px;
    }
    .header {
      background-color: #343a40;
      padding: 10px;
      color: #fff;
      text-align: center;
    }
    .nav-buttons {
      margin: 10px 0;
      text-align: center;
    }
    .nav-buttons .btn {
      margin: 0 4px;
      padding: 4px 10px;
      font-size: 13px;
    }
    .lot-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
    }
    .lot-grid input {
      padding: 4px 6px;
      font-size: 13px;
      height: 32px;
    }
    .form-group label {
      margin-bottom: 4px;
    }
    .form-control {
      padding: 4px 8px;
      height: 34px;
    }
    .card-body {
      padding: 15px;
    }
    #error-msg {
      margin-top: 5px;
    }
    .version-button {
      margin-top: 30px;
      margin-bottom: 20px;
      text-align: center;
    }
    .action-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .lot-error {
      background-color: #dc3545 !important;
      color: white !important;
      border: 2px solid #dc3545 !important;
      animation: shake 0.5s ease-in-out;
    }
    .lot-valid {
      background-color: #39FF14 !important;
      color: black !important;
      box-shadow: 0 0 10px #39FF14 !important;
      animation: neonGlow 1.5s ease-in-out infinite alternate;
    }
    
    @keyframes neonGlow {
      from {
        box-shadow: 0 0 5px #39FF14, 0 0 10px #39FF14, 0 0 15px #39FF14;
      }
      to {
        box-shadow: 0 0 10px #39FF14, 0 0 20px #39FF14, 0 0 30px #39FF14;
      }
    }
    .lot-warning {
      background-color: #fff3cd !important;
      color: #856404 !important;
      border: 1px solid #ffeaa7 !important;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-3px); }
      75% { transform: translateX(3px); }
    }
    .error-summary {
      background: #f8d7da;
      border: 1px solid #f5c6cb;
      border-radius: 5px;
      padding: 10px;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="header">
    <h4 class="m-0">Pallet Management System</h4>
  </div>

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

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="action-header mb-3">
          <h5 class="m-0">💾 Save Data</h5>
          <button onclick="window.location.href='add_manual.php'" class="btn btn-outline-secondary btn-sm">
            ➕ Add Manual
          </button>
        </div>

        <form action="save.php" method="POST" onsubmit="return validateForm()">
          <div class="form-group">
            <label for="user_code">User Code:</label>
            <?php $selected_user_code = $_GET['user_code'] ?? ''; ?>
            <select class="form-control" name="user_code" id="user_code" required>
              <option value="">-- เลือก User Code --</option>
              <?php 
                $codes = ["F3287","T0340","C4357","C8392","P9286","P6627","M2625","Yem"];
                foreach($codes as $c){
                  $sel = $selected_user_code === $c ? "selected" : "";
                  echo "<option value=\"$c\" $sel>$c</option>";
                }
              ?>
            </select>
          </div>

          <div class="form-group">
            <label for="qr_data">Scan QR Code:</label>
            <input type="text" class="form-control" name="qr_data" id="qr_data" 
                   placeholder="D3022A!NEW.ECN#08/01/25/A" required 
                   onchange="onQRCodeChange()">
          </div>

          <label>Enter 32 Lot Data:</label>
          <div class="lot-grid mt-2 mb-3">
            <?php for ($i = 1; $i <= 32; $i++): ?>
              <input type="text" class="form-control lot-input" name="lot_<?= $i ?>" 
                     placeholder="Lot <?= $i ?>" oninput="validateLotInput(this, <?= $i-1 ?>)" 
                     onblur="validateSingleLot(this, <?= $i-1 ?>)" data-lot-index="<?= $i-1 ?>">
            <?php endfor; ?>
          </div>

          <div id="error-msg" class="text-danger text-center font-weight-bold"></div>
          <div id="error-summary" class="error-summary" style="display: none;"></div>
          <button type="submit" class="btn btn-success btn-block mt-2">💾 Save Data</button>
        </form>
      </div>
    </div>

    <div class="version-button">
      <button onclick="window.location.href='version_update.php'" class="btn btn-outline-dark">
        🔌 Version : <strong><?= $latest_version ?></strong>
      </button>
    </div>
  </div>

  <script>
    let partConfigs = {}; // เก็บ Part Configuration จาก database
    let currentPartCode = '';
    let currentPartConfig = null;
    let validationErrors = [];

    // ดึง Part Configuration จาก database
    async function loadPartConfigs() {
      try {
        const response = await fetch('get_part_configs.php');
        const data = await response.json();
        partConfigs = data;
        console.log('Part configs loaded:', partConfigs);
      } catch (error) {
        console.error('Error loading part configs:', error);
      }
    }

    // เรียกใช้เมื่อโหลดหน้า
    document.addEventListener('DOMContentLoaded', function() {
      loadPartConfigs();
    });

    // เมื่อ QR Code เปลี่ยน
    function onQRCodeChange() {
      const qrData = document.getElementById('qr_data').value.trim();
      currentPartCode = getPartCodeFromQR(qrData);
      currentPartConfig = partConfigs[currentPartCode] || null;
      
      console.log('Part Code:', currentPartCode);
      console.log('Part Config:', currentPartConfig);
      
      // ตรวจสอบ Lot เดี่ยวเมื่อ blur (ออกจากช่อง)
    function validateSingleLot(input, index) {
      const value = input.value.trim();
      
      if (value && value.length !== 10) {
        input.classList.remove('lot-valid', 'lot-warning');
        input.classList.add('lot-error');
        showPopupError(`Lot "${value}" ต้องมี 10 ตัวอักษรเท่านั้น!`);
      }
      
      // เช็คทั้งหมดอีกครั้ง
      setTimeout(() => validateAllLots(), 100);
    }

    // ตรวจสอบ Lot ทั้งหมดใหม่
      setTimeout(() => validateAllLots(), 100);
    }

    // แยก Part Code จาก QR Data
    function getPartCodeFromQR(qrData) {
      if (qrData && qrData.includes('!')) {
        return qrData.split('!')[0];
      }
      return '';
    }

    // ตรวจสอบ Lot ตามการพิมพ์ (แสดงสีเบื้องต้น ไม่ popup)
    function validateLotInput(input, index) {
      const value = input.value.trim();
      
      // รีเซ็ตสี
      input.classList.remove('lot-valid', 'lot-error', 'lot-warning');
      
      if (value) {
        // แสดงสีเบื้องต้นตามความยาว (ไม่ popup)
        if (value.length === 10) {
          input.classList.add('lot-valid');
        } else if (value.length > 0) {
          input.classList.add('lot-warning'); // สีเหลืองระหว่างพิมพ์
        }
      }
      
      return true;
    }

    // ตรวจสอบ Lot ทั้งหมด
    function validateAllLots() {
      const inputs = document.querySelectorAll('.lot-input');
      const lots = [];
      validationErrors = [];
      
      // รีเซ็ตสีทั้งหมด
      inputs.forEach(input => {
        input.classList.remove('lot-valid', 'lot-error', 'lot-warning');
      });

      // เก็บข้อมูล Lot ทั้งหมด
      inputs.forEach((input, index) => {
        const value = input.value.trim();
        if (value) {
          lots.push({ value, input, index });
        }
      });

      let hasError = false;
      let duplicates = [];
      let lengthErrors = [];
      let partConfigErrors = [];

      // 1. ตรวจสอบความยาว
      lots.forEach(lot => {
        if (lot.value.length !== 10) {
          lengthErrors.push(lot.value);
          lot.input.classList.add('lot-error');
          hasError = true;
        }
      });

      // 2. ตรวจสอบ Lot ซ้ำ
      const valueCount = {};
      lots.forEach(lot => {
        if (lot.value.length === 10) { // ตรวจสอบเฉพาะ Lot ที่ความยาวถูกต้อง
          if (valueCount[lot.value]) {
            duplicates.push(lot.value);
            // ทำให้ทุก Lot ที่ซ้ำเป็นสีแดง
            lots.forEach(l => {
              if (l.value === lot.value) {
                l.input.classList.remove('lot-valid');
                l.input.classList.add('lot-error');
              }
            });
            hasError = true;
          } else {
            valueCount[lot.value] = 1;
          }
        }
      });

      // 3. ตรวจสอบ Part Configuration
      if (currentPartConfig && lots.length > 0) {
        const expectedCustomerShort = currentPartConfig.customer_short;
        const expectedCustomerOrder = currentPartConfig.customer_order;

        lots.forEach(lot => {
          if (lot.value.length === 10 && !duplicates.includes(lot.value)) {
            const actualDigit23 = lot.value.substring(1, 3); // Digit 2-3
            const actualDigit6 = lot.value.charAt(5); // Digit 6

            if (actualDigit23 !== expectedCustomerShort || actualDigit6 !== expectedCustomerOrder) {
              partConfigErrors.push({
                lot: lot.value,
                expected: `Digit 2-3: ${expectedCustomerShort}, Digit 6: ${expectedCustomerOrder}`,
                actual: `Digit 2-3: ${actualDigit23}, Digit 6: ${actualDigit6}`
              });
              lot.input.classList.remove('lot-valid');
              lot.input.classList.add('lot-error');
              hasError = true;
            } else if (!lot.input.classList.contains('lot-error')) {
              lot.input.classList.add('lot-valid');
            }
          }
        });
      } else {
        // ถ้าไม่มี Part Config ให้ Lot ที่ความยาวถูกต้องและไม่ซ้ำเป็นสีเขียว
        lots.forEach(lot => {
          if (lot.value.length === 10 && !duplicates.includes(lot.value) && !lot.input.classList.contains('lot-error')) {
            lot.input.classList.add('lot-valid');
          }
        });
      }

      // แสดง Error Messages
      if (lengthErrors.length > 0) {
        showPopupError(`Lot ต่อไปนี้ต้องมี 10 ตัวอักษร: ${lengthErrors.join(', ')}`);
        validationErrors.push(`ความยาวไม่ถูกต้อง: ${lengthErrors.join(', ')}`);
      }

      if (duplicates.length > 0) {
        const uniqueDuplicates = [...new Set(duplicates)];
        showPopupError(`พบ Lot ซ้ำกัน: ${uniqueDuplicates.join(', ')}`);
        validationErrors.push(`Lot ซ้ำกัน: ${uniqueDuplicates.join(', ')}`);
      }

      if (partConfigErrors.length > 0) {
        let errorMsg = `Part ${currentPartCode} - Lot ไม่ตรงกับ Configuration:\n`;
        partConfigErrors.forEach(error => {
          errorMsg += `• ${error.lot}: ต้องการ ${error.expected}, แต่พบ ${error.actual}\n`;
        });
        showPopupError(errorMsg);
        validationErrors.push(`Part Configuration ไม่ตรง: ${partConfigErrors.length} Lots`);
      }

      // แสดงสรุป Error
      updateErrorSummary();
      
      return !hasError;
    }

    // เช็ค Duplicate เท่านั้น (ไม่เช็คอย่างอื่น) - ไม่แสดง popup
    function checkDuplicatesOnly() {
      const inputs = document.querySelectorAll('.lot-input');
      const lots = [];
      
      // เก็บข้อมูล Lot ที่มี 10 ตัวอักษร
      inputs.forEach((input, index) => {
        const value = input.value.trim();
        if (value && value.length === 10) {
          lots.push({ value, input, index });
        }
      });

      // ตรวจสอบ Lot ซ้ำ
      const valueCount = {};
      const duplicates = [];
      
      lots.forEach(lot => {
        if (valueCount[lot.value]) {
          duplicates.push(lot.value);
        } else {
          valueCount[lot.value] = 1;
        }
      });

      // ทำให้ Lot ซ้ำเป็นสีแดง (ไม่แสดง popup ในขั้นตอนนี้)
      lots.forEach(lot => {
        if (duplicates.includes(lot.value)) {
          lot.input.classList.remove('lot-valid', 'lot-warning');
          lot.input.classList.add('lot-error');
        } else if (!lot.input.classList.contains('lot-error')) {
          lot.input.classList.remove('lot-warning');
          lot.input.classList.add('lot-valid');
        }
      });
    }

    // แสดง Popup Error
    function showPopupError(message) {
      alert('❌ ' + message);
    }

    // อัปเดตสรุป Error
    function updateErrorSummary() {
      const errorSummaryDiv = document.getElementById('error-summary');
      
      if (validationErrors.length > 0) {
        let html = '<h6>🚨 สรุปข้อผิดพลาด:</h6><ul>';
        validationErrors.forEach(error => {
          html += `<li>${error}</li>`;
        });
        html += '</ul>';
        errorSummaryDiv.innerHTML = html;
        errorSummaryDiv.style.display = 'block';
      } else {
        errorSummaryDiv.style.display = 'none';
      }
    }

    // ตรวจสอบก่อน Submit
    function validateForm() {
      const userCode = document.getElementById('user_code').value.trim();
      const qrData = document.getElementById('qr_data').value.trim();
      
      // 1. ตรวจสอบ User Code
      if (!userCode) {
        alert('❌ กรุณาเลือก User Code');
        document.getElementById('user_code').focus();
        return false;
      }

      // 2. ตรวจสอบ QR Code
      if (!qrData) {
        alert('❌ กรุณาสแกน QR Code');
        document.getElementById('qr_data').focus();
        return false;
      }

      // 3. ตรวจสอบ Lot ทั้งหมดอีกครั้ง
      const isValid = validateAllLots();
      
      if (!isValid) {
        alert('❌ กรุณาแก้ไขข้อผิดพลาดก่อนบันทึกข้อมูล');
        return false;
      }

      // 4. ตรวจสอบว่ามี Lot อย่างน้อย 1 ตัว
      const inputs = document.querySelectorAll('.lot-input');
      let hasLot = false;
      inputs.forEach(input => {
        if (input.value.trim()) {
          hasLot = true;
        }
      });

      if (!hasLot) {
        alert('❌ กรุณาใส่ข้อมูล Lot อย่างน้อย 1 รายการ');
        return false;
      }

      return true;
    }

    // Auto-validate เมื่อออกจากช่อง และเช็ค duplicate แบบ delay
    let inputTimeout = {};
    
    document.querySelectorAll('.lot-input').forEach((input, inputIndex) => {
      // เมื่อออกจากช่อง
      input.addEventListener('blur', function() {
        const lotIndex = parseInt(this.dataset.lotIndex);
        validateSingleLot(this, lotIndex);
      });
      
      // เมื่อพิมพ์ - รอให้พิมพ์เสร็จก่อน (สำหรับ barcode scanner)
      input.addEventListener('input', function() {
        const lotIndex = parseInt(this.dataset.lotIndex);
        
        // ล้าง timeout เก่า
        if (inputTimeout[lotIndex]) {
          clearTimeout(inputTimeout[lotIndex]);
        }
        
        // ตั้ง timeout ใหม่ - รอ 1.5 วินาทีหลังพิมพ์เสร็จ
        inputTimeout[lotIndex] = setTimeout(() => {
          checkDuplicatesOnly();
          
          // ถ้าครบ 10 ตัว ให้เช็ค Part Config ด้วย
          const value = this.value.trim();
          if (value.length === 10) {
            setTimeout(() => validateAllLots(), 100);
          }
        }, 1500); // รอ 1.5 วินาที
      });
    });

    console.log('JavaScript loaded successfully');
  </script>

  <!-- Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>