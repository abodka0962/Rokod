<?php
$host = 'localhost';
$db = 'eggs_inventory';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) { die("فشل الاتصال: " . $conn->connect_error); }

$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

$conn->query("CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE,
    egg_type VARCHAR(20),
    package_size VARCHAR(20),
    in_qty INT DEFAULT 0,
    out_qty INT DEFAULT 0
)");

$egg_types = ['بلدي', 'أبيض', 'أحمر'];
$package_sizes = ['كرتونة', 'نصف كرتونة'];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $egg_type = $_POST['egg_type'];
    $package_size = $_POST['package_size'];
    $in_qty = intval($_POST['in_qty']);
    $out_qty = intval($_POST['out_qty']);

    // حساب المخزون الحالي قبل التعديل
    $sql = "SELECT IFNULL(SUM(in_qty),0) AS total_in, IFNULL(SUM(out_qty),0) AS total_out 
            FROM inventory WHERE egg_type=? AND package_size=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $egg_type, $package_size);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $current_stock = intval($result['total_in']) - intval($result['total_out']);

    // التحقق من توفر الكمية أثناء الإخراج
    if ($out_qty > 0 && $out_qty > $current_stock) {
        $message = "<div class='alert-error'>خطأ: الكمية الخارجة ($out_qty) تتجاوز المخزون المتاح ($current_stock)!</div>";
    } else {
        $insert = $conn->prepare("INSERT INTO inventory (date, egg_type, package_size, in_qty, out_qty) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param('sssii', $date, $egg_type, $package_size, $in_qty, $out_qty);
        if ($insert->execute()) {
            $message = "<div class='alert-success'>تم حفظ العملية بنجاح.</div>";
        } else {
            $message = "<div class='alert-error'>حدث خطأ أثناء الحفظ.</div>";
        }
    }
}

// لجلب كل الكميات لكل نوع وحجم (لجدول الملخص وللتحديث الفوري في الفورم عبر JS)
$stock_data = [];
$sql = "SELECT egg_type, package_size, SUM(in_qty) AS total_in, SUM(out_qty) AS total_out
        FROM inventory
        GROUP BY egg_type, package_size";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $key = $row['egg_type'] . '|' . $row['package_size'];
    $stock_data[$key] = intval($row['total_in']) - intval($row['total_out']);
}

// لعرض الجدول النهائي
$stock = [];
foreach ($stock_data as $k => $v) {
    $key = str_replace('|', ' - ', $k);
    $stock[$key] = $v;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة المخزون | مؤسسة ركود التجارية</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            direction: rtl;
            margin: 40px;
            margin-right: 240px;
            background: #f5f7fa;
        }
        h2, h3 { color: #304ffe; }
        label {
            display: inline-block;
            width: 130px;
            font-size: 1.09em;
            margin-bottom: 7px;
        }
        input[type="text"], input[type="date"], input[type="number"], select {
            width: 75%;
            font-size: 1.09em;
            padding: 10px 8px;
            border: 1.5px solid #bdbdbd;
            border-radius: 8px;
            margin-bottom: 14px;
            transition: border 0.2s;
            background: #fff;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="date"]:focus, input[type="number"]:focus, select:focus {
            border: 2px solid #304ffe;
            outline: none;
            background: #f1f7fe;
        }
        input[readonly] {
            background: #f0f0f0;
            color: #666;
        }
        .form-box {
            border: 1.5px solid #ddd;
            padding: 28px 28px 10px 28px;
            width: 420px;
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
            font-size: 1.05em;
            text-align: center;
        }
        th {
            background: #304ffe;
            color: #fff;
            font-size: 1.13em;
        }
        @media (max-width:900px) {
            body { margin-right: 0; }
            .form-box { width: 97vw; max-width: 480px;}
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
    <script>
        // بيانات المخزون لكل نوع وحجم من PHP إلى جافاسكريبت
        var stockData = <?php echo json_encode($stock_data, JSON_UNESCAPED_UNICODE); ?>;

        function updateCurrentStock() {
            var eggType = document.getElementById('egg_type').value;
            var packageSize = document.getElementById('package_size').value;
            var key = eggType + "|" + packageSize;
            var current = stockData[key] !== undefined ? stockData[key] : 0;
            document.getElementById('current_stock').value = current;
        }
        window.onload = function() {
            updateCurrentStock();
            document.getElementById('egg_type').addEventListener('change', updateCurrentStock);
            document.getElementById('package_size').addEventListener('change', updateCurrentStock);
        };
    </script>
</head>
<body>
<!-- الشريط الجانبي -->
<div class="sidebar">
    <h2>لوحة التحكم</h2>
    <a href="index.php"><span class="material-icons" style="vertical-align:middle">home</span> الرئيسية</a>
    <a href="inventory_management.php" class="active"><span class="material-icons" style="vertical-align:middle">inventory_2</span> إدارة المخزون</a>
    <a href="suppliers.php"><span class="material-icons" style="vertical-align:middle">local_shipping</span> الموردين</a>
    <a href="customers.php"><span class="material-icons" style="vertical-align:middle">groups</span> العملاء</a>
    <a href="sales.php"><span class="material-icons" style="vertical-align:middle">point_of_sale</span> المبيعات</a>
    <a href="purchases.php"><span class="material-icons" style="vertical-align:middle">shopping_cart</span> المشتريات</a>
    <a href="expenses.php"><span class="material-icons" style="vertical-align:middle">money_off</span> المصروفات</a>
    <a href="reports.php"><span class="material-icons" style="vertical-align:middle">bar_chart</span> التقارير</a>
</div>

    <h2>إدارة المخزون</h2>
    <?php echo $message; ?>
    <div class="form-box">
        <form method="post">
            <label>التاريخ:</label>
            <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>"><br>
            <label>نوع البيض:</label>
            <select name="egg_type" id="egg_type" required>
                <?php foreach ($egg_types as $type): ?>
                    <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                <?php endforeach; ?>
            </select><br>
            <label>حجم العبوة:</label>
            <select name="package_size" id="package_size" required>
                <?php foreach ($package_sizes as $size): ?>
                    <option value="<?php echo $size; ?>"><?php echo $size; ?></option>
                <?php endforeach; ?>
            </select><br>
            <label>المخزون الحالي:</label>
            <input type="number" id="current_stock" readonly style="background:#f6f6f6;color:#304ffe;"><br>
            <label>الكمية الواردة:</label>
            <input type="number" name="in_qty" min="0" value="0"><br>
            <label>الكمية الخارجة:</label>
            <input type="number" name="out_qty" min="0" value="0"><br>
            <button type="submit" class="btn">
                <span class="material-icons" style="font-size:18px;vertical-align:middle;">save</span> حفظ
            </button>
        </form>
    </div>

    <h3>الكميات المتبقية لكل نوع وحجم</h3>
    <table>
        <tr>
            <th>نوع البيض - الحجم</th>
            <th>الكمية المتبقية</th>
        </tr>
        <?php foreach ($stock as $key => $qty): ?>
            <tr>
                <td><?php echo $key; ?></td>
                <td><?php echo $qty; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
<?php
$conn->close();
?>