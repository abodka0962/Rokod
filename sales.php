<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

// إعداد الاتصال بقاعدة البيانات
$host = 'localhost';
$db = 'eggs_inventory';
$user = 'root';
$pass = '';

// الاتصال بدون اختيار قاعدة بيانات لإنشائها إذا لم تكن موجودة
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) { die("فشل الاتصال: " . $conn->connect_error); }

// إنشاء قاعدة البيانات إذا لم تكن موجودة
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

// إنشاء جدول المبيعات إذا لم يكن موجوداً
$conn->query("CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date DATE,
    customer_id INT,
    egg_type VARCHAR(30),
    egg_size VARCHAR(30),
    quantity INT,
    unit_price DECIMAL(10,2),
    total_amount DECIMAL(12,2),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
)");

// التأكد من وجود مجلد الفواتير
$invoices_dir = __DIR__.'/invoices';
if (!is_dir($invoices_dir)) {
    mkdir($invoices_dir, 0777, true);
}

// جلب العملاء من قاعدة البيانات
$customers = [];
$res = $conn->query("SELECT id, name FROM customers ORDER BY name ASC");
while ($row = $res->fetch_assoc()) {
    $customers[] = $row;
}

$egg_types = ['بلدي', 'أبيض', 'أحمر'];
$egg_sizes = ['كرتونة', 'نصف كرتونة'];

$message = '';
$invoice_file = '';
$last_invoice_id = '';
$last_invoice_file = '';

if (isset($_POST['add_sale'])) {
    $sale_date  = $_POST['sale_date'];
    $customer_id = intval($_POST['customer_id']);
    $egg_type   = $_POST['egg_type'];
    $egg_size   = $_POST['egg_size'];
    $quantity   = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $total_amount = $quantity * $unit_price;

    // حفظ في قاعدة البيانات
    $stmt = $conn->prepare("INSERT INTO sales (sale_date, customer_id, egg_type, egg_size, quantity, unit_price, total_amount)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sissidd', $sale_date, $customer_id, $egg_type, $egg_size, $quantity, $unit_price, $total_amount);
    if ($stmt->execute()) {
        $sale_id = $conn->insert_id;
        // جلب بيانات العميل
        $cust = $conn->query("SELECT * FROM customers WHERE id=$customer_id")->fetch_assoc();
        // إنشاء ملف PDF للفاتورة
        ob_start();
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: 'DejaVu Sans', sans-serif;
                direction: rtl;
            }
            .inv-box { width: 480px; border: 1.5px solid #304ffe; border-radius: 18px; margin: 0 auto; background: #fff; padding: 24px 20px;}
            h2 { text-align:center; color: #304ffe; }
            .row { margin-bottom: 9px; }
            label { color: #222; font-weight: bold; }
            table { width:100%; margin-top:10px; border-collapse: collapse;}
            th, td { border: 1px solid #e0e0e0; padding: 8px 4px; text-align: center;}
            th { background: #f2f2f2; color: #222;}
            .tot { background: #304ffe; color: #fff; font-size:1.21em;}
        </style>
        </head>
        <body>
        <div class="inv-box">
            <h2>فاتورة بيع</h2>
            <div class="row"><label>التاريخ:</label> <?php echo htmlspecialchars($sale_date, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="row"><label>العميل:</label> <?php echo htmlspecialchars($cust['name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="row"><label>رقم الجوال:</label> <?php echo htmlspecialchars($cust['mobile'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="row"><label>العنوان الوطني:</label> <?php echo htmlspecialchars($cust['address'], ENT_QUOTES, 'UTF-8'); ?></div>
            <table>
                <tr>
                    <th>نوع البيض</th>
                    <th>الحجم</th>
                    <th>الكمية</th>
                    <th>سعر الوحدة</th>
                    <th>الإجمالي</th>
                </tr>
                <tr>
                    <td><?php echo htmlspecialchars($egg_type, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($egg_size, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($quantity, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format($unit_price,2); ?></td>
                    <td><?php echo number_format($total_amount,2); ?></td>
                </tr>
                <tr>
                    <td colspan="4" class="tot">المجموع الكلي</td>
                    <td class="tot"><?php echo number_format($total_amount,2); ?></td>
                </tr>
            </table>
            <div style="margin-top:22px;text-align:center;font-size:1.1em;color:#888">شكرًا لتعاملكم معنا</div>
        </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->set_option('defaultFont', 'DejaVu Sans');
        $dompdf->loadHtml($html, 'UTF-8'); // تفعيل دعم UTF-8
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();
        $file_name = 'فاتورة-'.$sale_id.'-'.date('Ymd_His').'.pdf';
        $output_path = $invoices_dir.'/'.$file_name;
        file_put_contents($output_path, $dompdf->output());
        $invoice_file = 'invoices/'.$file_name;
        $last_invoice_id = $sale_id;
        $last_invoice_file = $invoice_file;
        $message = "<div style='color:green;margin-top:30px;text-align:center;'>تم حفظ عملية البيع بنجاح.<br>
        <a class='btn print-btn' style='margin-top:14px;display:inline-block;' href='$invoice_file' target='_blank'>طباعة الفاتورة PDF</a>
        </div>";
    } else {
        $message = "<div style='color:red;'>حدث خطأ أثناء حفظ البيع.</div>";
    }
}

// زر طباعة الفاتورة الأخيرة (حتى لو لم تتم عملية بيع الآن)
if (isset($_GET['print_last_invoice'])) {
    // الحصول على آخر فاتورة محفوظة (يمكنك تطوير ذلك حسب الحاجة)
    $q = $conn->query("SELECT id FROM sales ORDER BY id DESC LIMIT 1");
    if ($q && $row = $q->fetch_assoc()) {
        $last_id = $row['id'];
        $pattern = $invoices_dir . '/فاتورة-' . $last_id . '-*.pdf';
        $files = glob($pattern);
        if ($files && isset($files[0])) {
            $invoice_file = 'invoices/' . basename($files[0]);
            header("Location: $invoice_file");
            exit;
        } else {
            $message = "<div style='color:red;'>لا يوجد ملف فاتورة للطلب الأخير.</div>";
        }
    } else {
        $message = "<div style='color:red;'>لا توجد أي عمليات بيع.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>صفحة المبيعات</title>
    <style>
        body {
            font-family: Tahoma, Arial, 'Segoe UI', 'Noto Kufi Arabic', 'Cairo', sans-serif;
            direction: rtl;
            margin: 40px;
            margin-right: 240px;
            background: #f5f7fa;
        }
        h2, h3 { color: #304ffe; }
        label {
            display: inline-block;
            width: 140px;
            font-size: 1.15em;
            margin-bottom: 7px;
        }
        input[type="text"], input[type="date"], input[type="number"], select {
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
        input[type="text"]:focus, input[type="date"]:focus, input[type="number"]:focus, select:focus {
            border: 2px solid #304ffe;
            outline: none;
            background: #f1f7fe;
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
            text-decoration: none;
        }
        .btn:hover {
            background: #1976d2;
            color: #fff;
        }
        .print-btn {
            background: #43a047;
            font-size: 1.14em;
        }
        .print-btn:hover {
            background: #2e7d32;
        }
        .total-box {
            font-size: 1.18em;
            background: #e3eafd;
            border-radius: 7px;
            display:inline-block;
            padding:7px 15px;
            margin-bottom: 14px;
            color: #304ffe;
        }
        .external-print-btn {
            background: #ff9800;
            color: #fff;
            font-size: 1.13em;
            border-radius: 7px;
            display: inline-block;
            padding: 10px 22px;
            margin-bottom: 15px;
            text-align: center;
            text-decoration: none;
        }
        .external-print-btn:hover {
            background: #f57c00;
            color: #fff;
        }
        @media (max-width:900px) {
            body { margin-right: 0; }
            .form-box { width: 97vw; max-width: 500px;}
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
        function calcTotal() {
            var qty = parseFloat(document.getElementById('quantity').value) || 0;
            var price = parseFloat(document.getElementById('unit_price').value) || 0;
            document.getElementById('total_amount').innerText = (qty * price).toFixed(2) + " ريال";
        }
    </script>
</head>
<body>
<!-- الشريط الجانبي -->
<div class="sidebar">
    <h2>لوحة التحكم</h2>
    <a href="index.php"><span class="material-icons" style="vertical-align:middle">home</span> الرئيسية</a>
    <a href="inventory_management.php"><span class="material-icons" style="vertical-align:middle">inventory_2</span> إدارة المخزون</a>
    <a href="suppliers.php"><span class="material-icons" style="vertical-align:middle">local_shipping</span> الموردين</a>
    <a href="customers.php"><span class="material-icons" style="vertical-align:middle">groups</span> العملاء</a>
    <a href="sales.php" class="active"><span class="material-icons" style="vertical-align:middle">point_of_sale</span> المبيعات</a>
    <a href="purchases.php"><span class="material-icons" style="vertical-align:middle">shopping_cart</span> المشتريات</a>
    <a href="expenses.php"><span class="material-icons" style="vertical-align:middle">money_off</span> المصروفات</a>
    <a href="reports.php"><span class="material-icons" style="vertical-align:middle">bar_chart</span> التقارير</a>
</div>
    <h2>إدارة المبيعات</h2>
    <div class="form-box">
        <!-- زر طباعة الفاتورة الأخيرة -->
        <form method="get" style="text-align:center;margin-bottom:8px;">
            <button type="submit" name="print_last_invoice" class="external-print-btn" title="طباعة آخر فاتورة PDF">
                <span class="material-icons" style="vertical-align:middle">print</span>
                طباعة آخر فاتورة PDF
            </button>
        </form>
        <form method="post">
            <label>التاريخ:</label>
            <input type="date" name="sale_date" required value="<?php echo date('Y-m-d'); ?>"><br>
            <label>اسم العميل:</label>
            <select name="customer_id" required>
                <option value="">اختر العميل</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select><br>
            <label>نوع البيض:</label>
            <select name="egg_type" required>
                <?php foreach ($egg_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select><br>
            <label>الحجم:</label>
            <select name="egg_size" required>
                <?php foreach ($egg_sizes as $size): ?>
                    <option value="<?php echo htmlspecialchars($size, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($size, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select><br>
            <label>الكمية:</label>
            <input type="number" name="quantity" id="quantity" min="1" required oninput="calcTotal()"><br>
            <label>سعر الوحدة:</label>
            <input type="number" name="unit_price" id="unit_price" min="0.01" step="0.01" required oninput="calcTotal()"><br>
            <div class="total-box">
                الإجمالي: <span id="total_amount">0.00 ريال</span>
            </div><br>
            <button class="btn print-btn" type="submit" name="add_sale">حفظ وطباعة الفاتورة</button>
        </form>
        <?php
            // عرض رسالة الحفظ وزر الطباعة في الأسفل بعد النموذج
            if (!empty($message)) {
                echo $message;
            }
        ?>
    </div>
</body>
</html>
<?php
$conn->close();
?>