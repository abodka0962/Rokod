<?php
// إعداد الاتصال بقاعدة البيانات
$host = "localhost";
$db = "rokood_db";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("خطأ في الاتصال: " . $conn->connect_error);
}

// إنشاء قاعدة البيانات إذا لم تكن موجودة
$conn->query("CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
$conn->select_db($db);

// إنشاء جدول المشتريات إذا لم يكن موجوداً مع الحقول الجديدة
$createTableSql = "CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_date DATE NOT NULL,
    supplier_name VARCHAR(100) NOT NULL,
    egg_type VARCHAR(50) NOT NULL,
    egg_size VARCHAR(30) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    vat_amount DECIMAL(12,2) NOT NULL,
    total_with_vat DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
$conn->query($createTableSql);

// معالجة الإدخال
$success = false;
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $purchase_date = $_POST['purchase_date'];
    $supplier_name = $_POST['supplier_name'];
    $egg_type = $_POST['egg_type'];
    $egg_size = $_POST['egg_size'];
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $total_amount = $quantity * $unit_price;
    $vat_amount = $total_amount * 0.15;
    $total_with_vat = $total_amount + $vat_amount;

    $stmt = $conn->prepare("INSERT INTO purchases (purchase_date, supplier_name, egg_type, egg_size, quantity, unit_price, total_amount, vat_amount, total_with_vat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssidddd", $purchase_date, $supplier_name, $egg_type, $egg_size, $quantity, $unit_price, $total_amount, $vat_amount, $total_with_vat);

    if ($stmt->execute()) {
        $success = true;
    } else {
        $error = "حدث خطأ أثناء حفظ البيانات: " . $stmt->error;
    }
    $stmt->close();
}

// جلب المشتريات من قاعدة البيانات
$purchases = [];
$result = $conn->query("SELECT * FROM purchases ORDER BY purchase_date DESC, id DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $purchases[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>صفحة المشتريات | مؤسسة ركود التجارية</title>
    <style>
        body {
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            margin: 0;
            background: #f5f7fa;
            direction: rtl;
        }
        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: 220px;
            height: 100%;
            background: linear-gradient(135deg, #304ffe 60%, #1976d2 100%);
            color: #fff;
            box-shadow: -2px 0 8px rgba(60,60,60,0.09);
            padding-top: 40px;
            z-index: 100;
        }
        .sidebar h2 {
            text-align: center;
            font-size: 1.2em;
            margin-bottom: 40px;
        }
        .sidebar a {
            display: block;
            padding: 15px 30px;
            color: #fff;
            text-decoration: none;
            font-size: 1.12em;
            margin-bottom: 8px;
            border-radius: 30px 0 0 30px;
            transition: background 0.2s;
        }
        .sidebar a.active,
        .sidebar a:hover {
            background: rgba(255,255,255,0.13);
        }
        .main-content {
            margin-right: 260px;
            padding: 50px 30px 30px 30px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .page-title {
            text-align: center;
            font-size: 2em;
            color: #374151;
            margin-bottom: 35px;
            letter-spacing: 1px;
        }
        .form-container {
            background: #fff;
            border-radius: 18px;
            padding: 32px 24px 24px 24px;
            box-shadow: 0 4px 18px 0 rgba(44, 62, 80, 0.08);
            border: 1px solid #f0f0f0;
            max-width: 440px;
            width: 100%;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 19px;
        }
        .form-row {
            display: flex;
            gap: 10px;
        }
        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        label {
            font-size: 15px;
            margin-bottom: 4px;
            color: #304ffe;
            font-weight: 500;
        }
        input, select {
            padding: 8px 10px;
            font-size: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: #fcfcfc;
            outline: none;
            transition: border 0.2s;
        }
        input:focus, select:focus {
            border: 1.5px solid #304ffe;
        }
        input[readonly] {
            background: #f4f7fa;
            color: #333;
            font-weight: bold;
        }
        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 12px;
        }
        .btn-submit {
            background: #304ffe;
            color: #fff;
            padding: 13px 26px;
            font-size: 18px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            transition: background 0.2s;
            font-weight: bold;
        }
        .btn-submit:hover {
            background: #1976d2;
        }
        .btn-pdf {
            background: #fff;
            color: #304ffe;
            border: 2px solid #304ffe;
            padding: 11px 22px;
            font-size: 16px;
            border-radius: 7px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-pdf:hover {
            background: #304ffe;
            color: #fff;
        }
        .alert-success {
            background: #d4edda;
            color: #26734d;
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 15px;
        }
        .alert-error {
            background: #f8d7da;
            color: #b71c1c;
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 15px;
        }
        .purchases-table-container {
            width: 100%;
            max-width: 1100px;
            margin: 55px auto 0 auto;
            background: #fff;
            box-shadow: 0 2px 16px 0 rgba(44, 62, 80, 0.10);
            border-radius: 18px;
            border: 1px solid #ececec;
            padding: 18px 12px 28px 12px;
        }
        .purchases-table-title {
            font-size: 1.2em;
            color: #304ffe;
            margin-bottom: 14px;
            text-align: right;
            font-weight: bold;
            letter-spacing: .5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 15px;
        }
        th, td {
            padding: 10px 6px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
        }
        th {
            background: #f3f6fd;
            color: #304ffe;
            font-weight: bold;
        }
        tr:last-child td {
            border-bottom: none;
        }
        @media (max-width: 900px) {
            .main-content {
                margin-right: 0;
                padding: 20px 2vw;
            }
            .sidebar {
                width: 100vw;
                height: 65px;
                position: fixed;
                top: 0;
                right: 0;
                display: flex;
                flex-direction: row;
                align-items: center;
                padding: 0 12px;
                z-index: 999;
            }
            .sidebar h2 {
                display: none;
            }
            .sidebar a {
                display: inline-block;
                padding: 8px 18px;
                margin-bottom: 0;
                border-radius: 30px;
                font-size: 1em;
                margin-right: 4px;
            }
            .purchases-table-container {
                padding: 8px 2px 15px 2px;
            }
            table, th, td {
                font-size: 13px;
            }
            .form-actions {
                flex-direction: column;
                gap: 7px;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <h2>لوحة التحكم</h2>
        <a href="index.php"><span class="material-icons" style="vertical-align:middle">home</span> الرئيسية</a>
        <a href="inventory_management.php"><span class="material-icons" style="vertical-align:middle">inventory_2</span> إدارة المخزون</a>
        <a href="suppliers.php"><span class="material-icons" style="vertical-align:middle">local_shipping</span> الموردين</a>
        <a href="customers.php"><span class="material-icons" style="vertical-align:middle">groups</span> العملاء</a>
        <a href="sales.php"><span class="material-icons" style="vertical-align:middle">point_of_sale</span> المبيعات</a>
        <a href="purchases.php" class="active"><span class="material-icons" style="vertical-align:middle">shopping_cart</span> المشتريات</a>
        <a href="expenses.php"><span class="material-icons" style="vertical-align:middle">money_off</span> المصروفات</a>
        <a href="reports.php"><span class="material-icons" style="vertical-align:middle">bar_chart</span> التقارير</a>
    </div>
    <div class="main-content">
        <div class="page-title">تسجيل عملية شراء جديدة</div>
        <div class="form-container">
            <?php if ($success): ?>
                <div class="alert-success">تم حفظ العملية بنجاح!</div>
            <?php elseif ($error): ?>
                <div class="alert-error"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST" id="purchaseForm" autocomplete="off">
                <div class="form-row">
                    <div class="form-group">
                        <label for="purchase_date">التاريخ</label>
                        <input type="date" name="purchase_date" id="purchase_date" required>
                    </div>
                    <div class="form-group">
                        <label for="supplier_name">اسم المورد</label>
                        <input type="text" name="supplier_name" id="supplier_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="egg_type">نوع البيض</label>
                        <select name="egg_type" id="egg_type" required>
                            <option value="">اختر النوع</option>
                            <option value="بلدي">بلدي</option>
                            <option value="أحمر">أحمر</option>
                            <option value="أبيض">أبيض</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="egg_size">الحجم</label>
                        <select name="egg_size" id="egg_size" required>
                            <option value="">اختر الحجم</option>
                            <option value="صغير">صغير</option>
                            <option value="متوسط">متوسط</option>
                            <option value="كبير">كبير</option>
                            <option value="جامبو">جامبو</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">الكمية</label>
                        <input type="number" name="quantity" id="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="unit_price">السعر للوحدة</label>
                        <input type="number" name="unit_price" id="unit_price" min="0" step="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label for="total_amount">المبلغ الإجمالي</label>
                        <input type="text" name="total_amount" id="total_amount" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label for="vat_amount">ضريبة القيمة المضافة (15%)</label>
                        <input type="text" name="vat_amount" id="vat_amount" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label for="total_with_vat">الإجمالي شامل الضريبة</label>
                        <input type="text" name="total_with_vat" id="total_with_vat" readonly>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit" name="save">
                        <span class="material-icons" style="font-size:18px;vertical-align:middle;">save</span> حفظ المشتريات
                    </button>
                    <button type="button" class="btn-pdf" onclick="printPurchaseFormPDF()">
                        <span class="material-icons" style="font-size:18px;vertical-align:middle;">picture_as_pdf</span> طباعة PDF
                    </button>
                </div>
            </form>
        </div>
        <?php if (!empty($purchases)): ?>
        <div class="purchases-table-container" id="purchases-table-container">
            <div class="purchases-table-title">جدول المشتريات</div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>التاريخ</th>
                        <th>اسم المورد</th>
                        <th>نوع البيض</th>
                        <th>الحجم</th>
                        <th>الكمية</th>
                        <th>السعر للوحدة</th>
                        <th>المبلغ الإجمالي</th>
                        <th>ضريبة القيمة المضافة</th>
                        <th>الإجمالي شامل الضريبة</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($purchases as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($row['purchase_date']) ?></td>
                        <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                        <td><?= htmlspecialchars($row['egg_type']) ?></td>
                        <td><?= htmlspecialchars($row['egg_size']) ?></td>
                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                        <td><?= number_format($row['unit_price'], 2) ?></td>
                        <td><?= number_format($row['total_amount'], 2) ?></td>
                        <td><?= number_format($row['vat_amount'], 2) ?></td>
                        <td><?= number_format($row['total_with_vat'], 2) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <script>
        // حساب المبالغ تلقائياً عند إدخال الكمية أو السعر
        document.addEventListener("DOMContentLoaded", function () {
            const quantity = document.getElementById('quantity');
            const unit_price = document.getElementById('unit_price');
            const total_amount = document.getElementById('total_amount');
            const vat_amount = document.getElementById('vat_amount');
            const total_with_vat = document.getElementById('total_with_vat');

            function updateTotals() {
                const q = parseFloat(quantity.value) || 0;
                const p = parseFloat(unit_price.value) || 0;
                const total = q * p;
                const vat = total * 0.15;
                const totalVat = total + vat;
                total_amount.value = total.toFixed(2);
                vat_amount.value = vat.toFixed(2);
                total_with_vat.value = totalVat.toFixed(2);
            }

            quantity.addEventListener('input', updateTotals);
            unit_price.addEventListener('input', updateTotals);
        });

        // طباعة النموذج PDF (طباعة فقط بيانات النموذج)
        function printPurchaseFormPDF() {
            var form = document.getElementById('purchaseForm');
            var purchase_date = document.getElementById('purchase_date').value;
            var supplier_name = document.getElementById('supplier_name').value;
            var egg_type = document.getElementById('egg_type').value;
            var egg_size = document.getElementById('egg_size').value;
            var quantity = document.getElementById('quantity').value;
            var unit_price = document.getElementById('unit_price').value;
            var total_amount = document.getElementById('total_amount').value;
            var vat_amount = document.getElementById('vat_amount').value;
            var total_with_vat = document.getElementById('total_with_vat').value;

            var content = `
                <div style="font-family:'Cairo',Arial,Tahoma,sans-serif;direction:rtl;padding:24px;">
                    <h2 style="color:#304ffe;">فاتورة شراء بيض</h2>
                    <table style="width:400px;border:1px solid #304ffe;background:#fff;border-radius:10px;box-shadow:0 2px 10px #e0e0e0;">
                        <tr><td style="padding:7px;font-weight:bold;">التاريخ</td><td style="padding:7px;">${purchase_date}</td></tr>
                        <tr><td style="padding:7px;font-weight:bold;">اسم المورد</td><td style="padding:7px;">${supplier_name}</td></tr>
                        <tr><td style="padding:7px;font-weight:bold;">نوع البيض</td><td style="padding:7px;">${egg_type}</td></tr>
                        <tr><td style="padding:7px;font-weight:bold;">الحجم</td><td style="padding:7px;">${egg_size}</td></tr>
                        <tr><td style="padding:7px;font-weight:bold;">الكمية</td><td style="padding:7px;">${quantity}</td></tr>
                        <tr><td style="padding:7px;font-weight:bold;">السعر للوحدة</td><td style="padding:7px;">${unit_price}</td></tr>
                        <tr><td style="padding:7px;font-weight:bold;">المبلغ الإجمالي</td><td style="padding:7px;">${total_amount}</td></tr>
                        <tr><td style="padding:7px;font-weight:bold;">ضريبة القيمة المضافة (15%)</td><td style="padding:7px;">${vat_amount}</td></tr>
                        <tr><td style="padding:7px;font-weight:bold;">الإجمالي شامل الضريبة</td><td style="padding:7px;">${total_with_vat}</td></tr>
                    </table>
                </div>
            `;
            var myWindow = window.open("", "Print", "width=600,height=600");
            myWindow.document.write(content);
            myWindow.document.close();
            myWindow.focus();
            myWindow.print();
        }
    </script>
</body>
</html>