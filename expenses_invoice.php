<?php
// بيانات الاتصال
$host = "localhost";
$db = "rokood_db";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("DB Error");

// جلب بيانات المصروف
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$row = null;
if ($id) {
    $result = $conn->query("SELECT * FROM expenses WHERE id=$id LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
}

// تنسيق رقم الفاتورة الخاص
function expense_invoice_number($id) {
    return 'EXP-' . str_pad($id, 6, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>فاتورة مصروف | مؤسسة ركود التجارية</title>
<style>
body { font-family: Tahoma, Arial, sans-serif; background: #f5f7fa; }
.invoice-box {
    background: #fff;
    max-width: 410px;
    margin: 40px auto;
    padding: 30px 28px 22px 28px;
    border-radius: 17px;
    box-shadow: 0 0 12px #d2d6e7;
    border-top: 7px solid #1976d2;
    border-bottom: 7px solid #1976d2;
}
.header { text-align: center; color: #1976d2; font-size: 1.5em; font-weight: bold; margin-bottom: 25px;}
.data-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 1.06em;}
.data-row .label { color: #555; font-weight: bold;}
.data-row .value { color: #232323;}
.table { width: 100%; border-collapse: collapse; margin: 22px 0 24px 0;}
.table th, .table td { border: 1px solid #e1e6ee; padding: 8px 7px; text-align: center;}
.table th { background: #e3e8fd; color: #0d47a1; font-weight: bold;}
.total-box { margin-top:20px; padding: 13px 17px; background: #f3f5fa; border-radius: 10px; border: 1px solid #e3e8fd; font-size:1.12em;}
.total-box .row { display: flex; justify-content: space-between; margin-bottom: 7px;}
.total-box .final { color: #1976d2; font-size: 1.19em; font-weight: bold;}
@media print {
    body { background: #fff; }
    .invoice-box { box-shadow: none; border-top: 4px solid #1976d2; border-bottom: 4px solid #1976d2;}
}
</style>
</head>
<body>
<div class="invoice-box">
    <div class="header">إيصال مصروف</div>
    <?php if ($row): ?>
        <div class="data-row">
            <div class="label">رقم الفاتورة:</div>
            <div class="value"><?= expense_invoice_number($row['id']) ?></div>
        </div>
        <div class="data-row">
            <div class="label">التاريخ:</div>
            <div class="value"><?= $row['expense_date'] ?></div>
        </div>
        <div class="data-row">
            <div class="label">نوع المصروف:</div>
            <div class="value"><?= htmlspecialchars($row['expense_type']) ?></div>
        </div>
        <div class="data-row">
            <div class="label">الوصف:</div>
            <div class="value"><?= htmlspecialchars($row['notes']) ?></div>
        </div>
        <table class="table">
            <tr>
                <th>المبلغ</th>
                <th>الضريبة (15%)</th>
                <th>المجموع شامل الضريبة</th>
            </tr>
            <tr>
                <td><?= number_format($row['amount'],2) ?></td>
                <td><?= number_format($row['vat_amount'],2) ?></td>
                <td><?= number_format($row['total_with_vat'],2) ?></td>
            </tr>
        </table>
        <div class="total-box">
            <div class="row">
                <span>الإجمالي قبل الضريبة:</span>
                <span><?= number_format($row['amount'],2) ?> ريال</span>
            </div>
            <div class="row">
                <span>قيمة الضريبة (15%):</span>
                <span><?= number_format($row['vat_amount'],2) ?> ريال</span>
            </div>
            <div class="row final">
                <span>الإجمالي بعد الضريبة:</span>
                <span><?= number_format($row['total_with_vat'],2) ?> ريال</span>
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