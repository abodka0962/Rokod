<?php
$host = "localhost";
$db = "rokood_db";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("DB Error");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$row = null;
if ($id) {
    $result = $conn->query("SELECT * FROM suppliers_payments WHERE id=$id LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
}

function supplier_invoice_number($id) {
    return 'SUP-' . str_pad($id, 6, '0', STR_PAD_LEFT);
}
$tax_number = "1234567890";
$company_name = "مؤسسة ركود التجارية";
$mobile = "0542287038";
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>سند صرف مورد | <?= $company_name ?></title>
<style>
body { font-family: Tahoma, Arial, sans-serif; background: #f5f7fa; }
.invoice-box { background: #fff; max-width: 430px; margin: 40px auto; padding: 30px 28px 22px 28px; border-radius: 17px; box-shadow: 0 0 12px #d2d6e7; border-top: 7px solid #6d4c41; border-bottom: 7px solid #6d4c41;}
.header-brand { text-align: center; margin-bottom: 15px; font-size: 1.14em;}
.header-brand .title { font-size: 1.18em; color: #6d4c41; font-weight: bold; margin-bottom: 3px;}
.header-brand .info { color: #222; font-size: 1em; margin-bottom: 2px;}
.header-brand .tax { font-weight: bold; color: #d32f2f; margin-bottom: 2px;}
.header { text-align: center; color: #6d4c41; font-size: 1.4em; font-weight: bold; margin-bottom: 25px;}
.data-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 1.06em;}
.data-row .label { color: #555; font-weight: bold;}
.data-row .value { color: #232323;}
.table { width: 100%; border-collapse: collapse; margin: 22px 0 24px 0;}
.table th, .table td { border: 1px solid #e1e6ee; padding: 8px 7px; text-align: center;}
.table th { background: #efebe9; color: #4e342e; font-weight: bold;}
.total-box { margin-top:20px; padding: 13px 17px; background: #f9f7f6; border-radius: 10px; border: 1px solid #d7ccc8; font-size:1.12em;}
.total-box .row { display: flex; justify-content: space-between; margin-bottom: 7px;}
.total-box .final { color: #6d4c41; font-size: 1.19em; font-weight: bold;}
@media print { body { background: #fff; } .invoice-box { box-shadow: none; border-top: 4px solid #6d4c41; border-bottom: 4px solid #6d4c41;}}
</style>
</head>
<body>
<div class="invoice-box">
    <div class="header-brand">
        <div class="title"><?= $company_name ?></div>
        <div class="tax"> الرقم الضريبي: <?= $tax_number ?> </div>
        <div class="info"> جوال: <?= $mobile ?> </div>
    </div>
    <div class="header">سند صرف مورد</div>
    <?php if ($row): ?>
        <div class="data-row">
            <div class="label">رقم السند:</div>
            <div class="value"><?= supplier_invoice_number($row['id']) ?></div>
        </div>
        <div class="data-row">
            <div class="label">التاريخ:</div>
            <div class="value"><?= $row['payment_date'] ?></div>
        </div>
        <div class="data-row">
            <div class="label">اسم المورد:</div>
            <div class="value"><?= htmlspecialchars($row['supplier_name']) ?></div>
        </div>
        <div class="data-row">
            <div class="label">الوصف:</div>
            <div class="value"><?= htmlspecialchars($row['notes']) ?></div>
        </div>
        <table class="table">
            <tr>
                <th>المبلغ</th>
                <th>الضريبة (15%)</th>
                <th>الإجمالي مع الضريبة</th>
            </tr>
            <?php
                $amount = $row['amount'];
                $vat = round($amount * 0.15, 2);
                $total = $amount + $vat;
            ?>
            <tr>
                <td><?= number_format($amount,2) ?></td>
                <td><?= number_format($vat,2) ?></td>
                <td><?= number_format($total,2) ?></td>
            </tr>
        </table>
        <div class="total-box">
            <div class="row">
                <span>الإجمالي قبل الضريبة:</span>
                <span><?= number_format($amount,2) ?> ريال</span>
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
        <div style="color:#b71c1c;text-align:center;margin:60px 0;">لا يوجد بيانات لهذا السند.</div>
    <?php endif; ?>
</div>
<script>window.focus();</script>
</body>
</html>
<?php $conn->close(); ?>