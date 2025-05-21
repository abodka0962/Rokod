<?php
// إعداد الاتصال بقاعدة البيانات (تأكد من تعديل البيانات حسب إعداداتك)
$host = "localhost";
$db = "rokood_db"; // اسم قاعدة البيانات
$user = "root";    // اسم المستخدم
$pass = "";        // كلمة المرور

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("خطأ في الاتصال: " . $conn->connect_error);
}

// إنشاء جدول المشتريات تلقائياً إذا لم يكن موجوداً
$createTableSql = "CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_date DATE NOT NULL,
    supplier_name VARCHAR(100) NOT NULL,
    egg_type VARCHAR(50) NOT NULL,
    egg_size VARCHAR(30) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
$conn->query($createTableSql);

// معالجة الإدخال
$success = false;
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchase_date = $_POST['purchase_date'];
    $supplier_name = $_POST['supplier_name'];
    $egg_type = $_POST['egg_type'];
    $egg_size = $_POST['egg_size'];
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $total_amount = $quantity * $unit_price;

    $stmt = $conn->prepare("INSERT INTO purchases (purchase_date, supplier_name, egg_type, egg_size, quantity, unit_price, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssidd", $purchase_date, $supplier_name, $egg_type, $egg_size, $quantity, $unit_price, $total_amount);

    if ($stmt->execute()) {
        $success = true;
    } else {
        $error = "حدث خطأ أثناء حفظ البيانات: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>صفحة المشتريات | مؤسسة ركود التجارية</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            background: #f8f9fb;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            margin: 0;
            display: flex;
        }
        /* الشريط الجانبي */
        .sidebar {
            width: 210px;
            background: #27496d;
            color: #fff;
            height: 100vh;
            position: fixed;
            right: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            align-items: right;
            padding-top: 30px;
            box-shadow: -2px 0 8px #e0e0e0;
        }
        .sidebar h3 {
            text-align: center;
            margin-bottom: 28px;
            letter-spacing: 2px;
        }
        .sidebar a {
            color: #fff;
            padding: 15px 30px;
            text-decoration: none;
            display: block;
            font-size: 17px;
            border-radius: 12px 0 0 12px;
            margin-bottom: 6px;
            transition: background 0.2s;
        }
        .sidebar a.active, .sidebar a:hover {
            background: #142850;
        }
        /* محتوى الصفحة */
        .main-content {
            margin-right: 210px;
            width: 100%;
            padding: 36px 0 0 0;
            min-height: 100vh;
        }
        .container {
            max-width: 560px;
            margin: auto;
            background: #fff;
            padding: 32px 30px 22px 30px;
            border-radius: 14px;
            box-shadow: 0 2px 16px #e1e3ea;
            margin-top: 60px;
        }
        .container h2 {
            color: #27496d;
            text-align: center;
            margin-bottom: 36px;
            font-size: 26px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .form-row {
            display: flex;
            gap: 18px;
        }
        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        label {
            font-size: 16px;
            margin-bottom: 5px;
            color: #27496d;
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
            border: 1.5px solid #27496d;
        }
        input[readonly] {
            background: #f4f7fa;
            color: #333;
            font-weight: bold;
        }
        .btn-submit {
            background: #27496d;
            color: #fff;
            padding: 13px 0;
            font-size: 18px;
            border: none;
            border-radius: 7px;
            margin-top: 18px;
            cursor: pointer;
            transition: background 0.2s;
            font-weight: bold;
        }
        .btn-submit:hover {
            background: #142850;
        }
        .alert-success {
            background: #d4edda;
            color: #26734d;
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 16px;
        }
        .alert-error {
            background: #f8d7da;
            color: #b71c1c;
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 16px;
        }
        @media (max-width: 700px) {
            .container {padding: 16px 7px;}
            .sidebar {width: 100px;}
            .sidebar a {font-size: 14px; padding: 13px 12px;}
            .main-content {margin-right: 100px;}
            .form-row {flex-direction: column; gap: 2px;}
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3>ركود التجارية</h3>
        <a href="dashboard.php">لوحة التحكم</a>
        <a href="purchases.php" class="active">المشتريات</a>
        <a href="sales.php">المبيعات</a>
        <a href="suppliers.php">الموردون</a>
        <a href="reports.php">التقارير</a>
        <a href="logout.php">تسجيل الخروج</a>
    </div>
    <div class="main-content">
        <div class="container">
            <h2>تسجيل عملية شراء جديدة</h2>
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
                <button type="submit" class="btn-submit">حفظ المشتريات</button>
            </form>
        </div>
    </div>
    <script>
        // حساب المبلغ الإجمالي تلقائياً عند إدخال الكمية أو السعر
        document.addEventListener("DOMContentLoaded", function () {
            const quantity = document.getElementById('quantity');
            const unit_price = document.getElementById('unit_price');
            const total_amount = document.getElementById('total_amount');

            function updateTotal() {
                const q = parseFloat(quantity.value) || 0;
                const p = parseFloat(unit_price.value) || 0;
                total_amount.value = (q * p).toFixed(2);
            }

            quantity.addEventListener('input', updateTotal);
            unit_price.addEventListener('input', updateTotal);
        });
    </script>
</body>
</html>