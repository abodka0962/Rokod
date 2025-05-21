<?php
$host = "localhost";
$db = "rokood_db";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("DB Error");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$row = null;
$items = [];
if ($id) {
    $result = $conn->query("SELECT * FROM purchases WHERE id=$id LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
    $itemsResult = $conn->query("SELECT * FROM purchases_items WHERE purchase_id=$id");
    if ($itemsResult) {
        while($item = $itemsResult->fetch_assoc()) $items[] = $item;
    }
}

function purchase_invoice_number($id) {
    return 'PUR-' . str_pad($id, 6, '0', STR_PAD_LEFT);
}
$tax_number = "1234567890";
$company_name = "مؤسسة ركود التجارية";
$mobile = "0542287038";
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>فاتورة مشتريات | <?= $company_name ?></title>
<style>
body { font-family: Tahoma, Arial, sans-serif; background: #f5f7fa; }
.invoice-box {
    background: #fff;
    max-width: 510px;
    margin: 40px auto;
    padding: 30px 28px 22px 28px;
    border-radius: 17px;
    box-shadow: 0 0 12px #d2d6e7;
    border-top: 7px solid #ff9800;
    border-bottom: 7px solid #ff9800;
}
.header-brand {
    text-align: center;
    margin-bottom: 15px;
    font-size: 1.14em;
}
.header-brand .title {
    font-size: 1.18em;
    color: #ef6c00;
    font-weight: bold;
    margin-bottom: 3px;
    letter-spacing: 1px;
}
.header-brand .info {
    color: #222;
    font-size: 1em;
    margin-bottom: 2px;
}
.header-brand .tax {
    font-weight: bold;
    color: #d32f2f;
    margin-bottom: 2px;
}
.header { text-align: center; color: #ef6c00; font-size: 1.5em; font-weight: bold; margin-bottom: 25px;}
.data-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 1.06em;}
.data-row .label { color: #555; font-weight: bold;}
.data-row .value { color: #232323;}
.table { width: 100%; border-collapse: collapse; margin: 22px 0 24px 0;}
.table th, .table td { border: 1px solid #e1e6ee; padding: 8px 7px; text-align: center;}
.table th { background: #fff3e0; color: #e65100; font-weight: bold;}
.total-box { margin-top:20px; padding: 13px 17px; background: #fdf6ed; border-radius: 10px; border: 1px solid #ffe0b2; font-size:1.13em;}
.total-box .row { display: flex; justify-content: space-between; margin-bottom: 7px;}
.total-box .final { color: #ef6c00; font-size: 1.19em; font-weight: bold;}
@media print {
    body { background: #fff; }
    .invoice-box { box-shadow: none; border-top: 4px solid #ff9800; border-bottom: 4px solid #ff9800;}
}
</style>
</head>
<body>
<div class="invoice-box">
    <div class="header-brand">
        <div class="title"><?= $company_name ?></div>
        <div class="tax"> الرقم الضريبي: <?= $tax_number ?> </div>
        <div class="info"> جوال: <?= $mobile ?> </div>
    </div>
    <div class="header">فاتورة مشتريات</div>
    <?php if ($row): ?>
        <div class="data-row">
            <div class="label">رقم الفاتورة:</div>
            <div class="value"><?= purchase_invoice_number($row['id']) ?></div>
        </div>
        <div class="data-row">
            <div class="label">التاريخ:</div>
            <div class="value"><?= $row['purchase_date'] ?></div>
        </div>
        <div class="data-row">
            <div class="label">المورد:</div>
            <div class="value"><?= htmlspecialchars($row['supplier_name']) ?></div>
        </div>
        <table class="table">
            <tr>
                <th>الصنف</th>
                <th>الكمية</th>
                <th>السعر للوحدة</th>
                <th>الإجمالي</th>
            </tr>
            <?php
                $subtotal = 0;
                foreach ($items as $item):
                    $item_total = $item['qty'] * $item['unit_price'];
                    $subtotal += $item_total;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= $item['qty'] ?></td>
                <td><?= number_format($item['unit_price'],2) ?></td>
                <td><?= number_format($item_total,2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
            $vat = round($subtotal * 0.15, 2);
            $total = $subtotal + $vat;
        ?>
        <div class="total-box">
            <div class="row">
                <span>الإجمالي قبل الضريبة:</span>
                <span><?= number_format($subtotal,2) ?> ريال</span>
            </div>
            <div class="row">
                <span>قيمة الضريبة (15%):</span>
                <span><?= number_format($vat,2) ?> ريال</span>
            </div>
            <div class="row final">
                <span>الإجمالي بعد الضريبة:</span>
                <span><?= number_format($total,2) ?> ريال</span>
            </div>
        </div>
    <?php else: ?>
        <div style="color:#b71c1c;text-align:center;margin:60px 0;">لا يوجد بيانات لهذه الفاتورة.</div>
    <?php endif; ?>
</div>
<script>window.focus();</script>
</body>
</html>
<?php $conn->close(); ?>