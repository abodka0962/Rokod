<?php
// إعداد الاتصال بقاعدة البيانات
$host = 'localhost';
$db = 'eggs_inventory';
$user = 'root';
$pass = '';

// الاتصال بدون اختيار قاعدة بيانات لإنشائها إذا لم تكن موجودة
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// إنشاء قاعدة البيانات إذا لم تكن موجودة
$conn->query("CREATE DATABASE IF NOT EXISTS $db");

// اختيار قاعدة البيانات
$conn->select_db($db);

// إنشاء جدول الموردين إذا لم يكن موجوداً
$conn->query("CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    mobile VARCHAR(20),
    notes TEXT
)");

// إنشاء جدول التوريدات إذا لم يكن موجوداً
$conn->query("CREATE TABLE IF NOT EXISTS supplies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT,
    supply_date DATE,
    quantity INT,
    notes TEXT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
)");

// إضافة مورد جديد
$message = '';
if (isset($_POST['add_supplier'])) {
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $notes = $_POST['notes'];
    $stmt = $conn->prepare("INSERT INTO suppliers (name, mobile, notes) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $name, $mobile, $notes);
    if ($stmt->execute()) {
        $message = "<div style='color:green;'>تمت إضافة المورد بنجاح.</div>";
    } else {
        $message = "<div style='color:red;'>حدث خطأ أثناء الإضافة!</div>";
    }
}

// إضافة توريد جديد لمورد
if (isset($_POST['add_supply'])) {
    $supplier_id = intval($_POST['supplier_id']);
    $supply_date = $_POST['supply_date'];
    $quantity = intval($_POST['quantity']);
    $notes = $_POST['supply_notes'];
    $stmt = $conn->prepare("INSERT INTO supplies (supplier_id, supply_date, quantity, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isis', $supplier_id, $supply_date, $quantity, $notes);
    if ($stmt->execute()) {
        $message = "<div style='color:green;'>تمت إضافة التوريد بنجاح.</div>";
    } else {
        $message = "<div style='color:red;'>حدث خطأ أثناء إضافة التوريد!</div>";
    }
}

// جلب الموردين
$suppliers = [];
$res = $conn->query("SELECT * FROM suppliers ORDER BY id DESC");
while ($row = $res->fetch_assoc()) {
    $suppliers[] = $row;
}

// عرض تفاصيل مورد
$supplier_details = null;
$supplier_supplies = [];
if (isset($_GET['details'])) {
    $supplier_id = intval($_GET['details']);
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id=?");
    $stmt->bind_param('i', $supplier_id);
    $stmt->execute();
    $supplier_details = $stmt->get_result()->fetch_assoc();

    // جلب التوريدات الخاصة بهذا المورد
    $stmt2 = $conn->prepare("SELECT * FROM supplies WHERE supplier_id=? ORDER BY supply_date DESC, id DESC");
    $stmt2->bind_param('i', $supplier_id);
    $stmt2->execute();
    $supplier_supplies = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>صفحة الموردين</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            direction: rtl;
            margin: 40px;
            margin-right: 240px;
            background: #f5f7fa;
        }
        h2, h3, h4 {
            color: #304ffe;
        }
        label {
            display: inline-block;
            width: 140px;
            font-size: 1.15em;
            margin-bottom: 7px;
        }
        input[type="text"], input[type="date"], input[type="number"], textarea {
            width: 85%;
            font-size: 1.15em;
            padding: 12px 10px;
            border: 1.5px solid #bdbdbd;
            border-radius: 8px;
            margin-bottom: 14px;
            transition: border 0.2s;
            background: #fff;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="date"]:focus, input[type="number"]:focus, textarea:focus {
            border: 2px solid #304ffe;
            outline: none;
            background: #f1f7fe;
        }
        textarea {
            resize: vertical;
            min-height: 50px;
            max-height: 160px;
        }
        table {
            border-collapse: collapse;
            margin-top: 30px;
            background: #fff;
            width: 100%;
            box-shadow: 0 2px 12px 0 rgba(80,80,80,0.08);
            border-radius: 12px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 14px 18px;
            font-size: 1.07em;
        }
        th {
            background: #304ffe;
            color: #fff;
            font-size: 1.13em;
        }
        .form-box {
            border: 1.5px solid #ddd;
            padding: 28px 28px 10px 28px;
            width: 460px;
            margin-bottom: 35px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 14px 0 rgba(44, 62, 80, 0.09);
        }
        .btn {
            padding: 10px 26px;
            border-radius: 7px;
            border: none;
            background: #304ffe;
            color: #fff;
            font-size: 1.12em;
            font-weight: bold;
            margin: 7px 0;
            cursor:pointer;
            transition: background 0.18s, color 0.18s;
        }
        .btn:hover {
            background: #1976d2;
            color: #fff;
        }
        .add-btn {
            margin-bottom: 18px;
            background: #43a047;
            color: #fff;
            border: none;
        }
        .add-btn:hover {
            background: #388e3c;
        }
        .details-box {
            border: 1.5px solid #bbb;
            padding: 22px 28px 8px 28px;
            background: #fcfcfc;
            margin-bottom: 20px;
            border-radius: 13px;
            width: 520px;
            box-shadow: 0 2px 8px 0 rgba(80,80,80,0.07);
        }
        .close-btn {
            background:#eee;
            color:#666;
            border: none;
            font-weight: normal;
        }
        .close-btn:hover {
            background:#ccc;
            color: #222;
        }
        @media (max-width:900px) {
            body { margin-right: 0; }
            .form-box, .details-box { width: 97vw; max-width: 500px;}
            table { font-size: 0.98em;}
        }
        /* الشريط الجانبي */
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
        @media (max-width: 900px) {
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
        }
    </style>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<!-- الشريط الجانبي -->
<div class="sidebar">
    <h2>لوحة التحكم</h2>
    <a href="index.php"><span class="material-icons" style="vertical-align:middle">home</span> الرئيسية</a>
    <a href="inventory_management.php"><span class="material-icons" style="vertical-align:middle">inventory_2</span> إدارة المخزون</a>
    <a href="suppliers.php" class="active"><span class="material-icons" style="vertical-align:middle">local_shipping</span> الموردين</a>
    <a href="customers.php"><span class="material-icons" style="vertical-align:middle">groups</span> العملاء</a>
    <a href="sales.php"><span class="material-icons" style="vertical-align:middle">point_of_sale</span> المبيعات</a>
    <a href="purchases.php"><span class="material-icons" style="vertical-align:middle">shopping_cart</span> المشتريات</a>
    <a href="expenses.php"><span class="material-icons" style="vertical-align:middle">money_off</span> المصروفات</a>
    <a href="reports.php"><span class="material-icons" style="vertical-align:middle">bar_chart</span> التقارير</a>
</div>
    <h2>إدارة الموردين</h2>
    <?php echo $message; ?>

    <?php if ($supplier_details): ?>
        <div class="details-box">
            <h3>تفاصيل المورد</h3>
            <div style="margin-bottom:12px;">
                <b>الاسم:</b> <?php echo htmlspecialchars($supplier_details['name']); ?><br>
                <b>رقم الجوال:</b> <?php echo htmlspecialchars($supplier_details['mobile']); ?><br>
                <b>الملاحظات:</b> <?php echo htmlspecialchars($supplier_details['notes']); ?><br>
            </div>
            <hr>
            <h4>إضافة توريد جديد لهذا المورد</h4>
            <form method="post">
                <input type="hidden" name="supplier_id" value="<?php echo $supplier_details['id']; ?>">
                <label>التاريخ:</label>
                <input type="date" name="supply_date" required value="<?php echo date('Y-m-d'); ?>"><br>
                <label>الكمية:</label>
                <input type="number" name="quantity" min="1" required><br>
                <label>ملاحظات:</label>
                <textarea name="supply_notes" rows="2"></textarea><br>
                <button class="btn" type="submit" name="add_supply">إضافة توريد</button>
            </form>
            <hr>
            <h4>سجل التوريدات</h4>
            <?php if (count($supplier_supplies)): ?>
                <table>
                    <tr>
                        <th>التاريخ</th>
                        <th>الكمية</th>
                        <th>ملاحظات</th>
                    </tr>
                    <?php foreach ($supplier_supplies as $supply): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($supply['supply_date']); ?></td>
                            <td><?php echo htmlspecialchars($supply['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($supply['notes']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div>لا توجد توريدات لهذا المورد بعد.</div>
            <?php endif; ?>
            <br>
            <a class="btn close-btn" href="suppliers.php">عودة لقائمة الموردين</a>
        </div>
    <?php else: ?>
        <button class="btn add-btn" onclick="document.getElementById('addSupplierBox').style.display='block';">إضافة مورد جديد</button>
        <div class="form-box" id="addSupplierBox" style="display:none;">
            <h3>إضافة مورد جديد</h3>
            <form method="post">
                <label>اسم المورد:</label>
                <input type="text" name="name" required><br>
                <label>رقم الجوال:</label>
                <input type="text" name="mobile" required><br>
                <label>ملاحظات:</label>
                <textarea name="notes" rows="2"></textarea><br>
                <button class="btn" type="submit" name="add_supplier">إضافة المورد</button>
                <button class="btn close-btn" type="button" onclick="document.getElementById('addSupplierBox').style.display='none';">إلغاء</button>
            </form>
        </div>
        <h3>قائمة الموردين</h3>
        <table>
            <tr>
                <th>الاسم</th>
                <th>رقم الجوال</th>
                <th>ملاحظات</th>
                <th>الإجراءات</th>
            </tr>
            <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['mobile']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['notes']); ?></td>
                    <td>
                        <a class="btn" style="background:#ff9800;" href="?details=<?php echo $supplier['id']; ?>">عرض التفاصيل</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <script>
            // إظهار نافذة إضافة مورد تلقائياً إذا حدث خطأ في الإضافة
            <?php if (isset($_POST['add_supplier']) && strpos($message, 'خطأ') !== false): ?>
            document.getElementById('addSupplierBox').style.display = 'block';
            <?php endif; ?>
        </script>
    <?php endif; ?>
</body>
</html>
<?php
$conn->close();
?>