<?php
/*
 * قالب فاتورة موحد لجميع الأقسام
 * المتغيرات المطلوبة:
 * - $invTitle: عنوان الفاتورة (مثال: فاتورة مشتريات، فاتورة مبيعات، سند صرف...)
 * - $dataHeaders: عناوين الأعمدة (مصفوفة)
 * - $dataRows: بيانات الجدول (مصفوفة من صفوف)
 * - $invoiceInfo: معلومات إضافية (مصفوفة ['label' => 'القيمة'])
 * - $invoiceDate: تاريخ الفاتورة (اختياري)
 * - $invoiceNumber: رقم الفاتورة (اختياري)
 *
 * مثال الاستخدام:
 * include "invoice_template.php";
 */
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?= isset($invTitle) ? htmlspecialchars($invTitle) : 'فاتورة' ?> | مؤسسة ركود التجارية</title>
    <style>
        body { font-family: 'Cairo', Tahoma, Arial, sans-serif; margin:0; background:#f5f7fa; direction:rtl; }
        .invoice-container {
            max-width: 740px;
            margin: 38px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 18px 0 rgba(44, 62, 80, 0.08);
            border: 1px solid #e1e1e1;
            padding: 36px 30px 30px 30px;
        }
        .inv-header {
            border-bottom: 2px solid #304ffe;
            padding-bottom: 10px;
            margin-bottom: 22px;
            position: relative;
            text-align: center;
        }
        .inv-logo {
            width: 76px;
            height: 76px;
            object-fit: contain;
            display: block;
            margin: 0 auto 6px auto;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 8px #304ffe15;
        }
        .inv-header .company {
            font-size: 1.6em;
            font-weight: bold;
            color: #304ffe;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .inv-header .contacts {
            font-size: 1.03em;
            color: #444;
            margin-top: 4px;
        }
        .inv-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .inv-title {
            font-size: 1.3em;
            color: #1976d2;
            font-weight: bold;
            letter-spacing: .5px;
        }
        .inv-meta {
            text-align: left;
            font-size: 1em;
            color: #555;
        }
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0 18px 0;
        }
        .inv-table th, .inv-table td {
            border: 1px solid #eee;
            padding: 8px 10px;
            text-align: center;
        }
        .inv-table th {
            background: #f3f6fd;
            color: #304ffe;
            font-weight: bold;
        }
        .info-block {
            margin-top: 16px;
            margin-bottom: 4px;
        }
        .info-block ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .info-block li {
            margin-bottom: 4px;
            font-size: 1em;
            color: #222;
        }
        @media print {
            body { background: #fff; }
            .invoice-container { margin: 0; box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- الهيدر الموحد -->
        <div class="inv-header">
            <img src="Icons/main.png" class="inv-logo" alt="icon.png">
            <div class="company">مؤسسة ركود التجارية</div>
            <div class="contacts">
                الرقم الضريبي: <b>---</b> &nbsp;|&nbsp; جوال: <b>0542287038</b>
            </div>
        </div>
        <!-- عنوان الفاتورة ومعلوماتها -->
        <div class="inv-title-row">
            <div class="inv-title"><?= isset($invTitle) ? htmlspecialchars($invTitle) : 'فاتورة' ?></div>
            <div class="inv-meta">
                <?php if (!empty($invoiceNumber)): ?>
                    رقم الفاتورة: <b><?= htmlspecialchars($invoiceNumber) ?></b><br>
                <?php endif; ?>
                <?php if (!empty($invoiceDate)): ?>
                    التاريخ: <b><?= htmlspecialchars($invoiceDate) ?></b>
                <?php endif; ?>
            </div>
        </div>
        <!-- جدول البيانات -->
        <table class="inv-table">
            <thead>
                <tr>
                    <?php foreach ($dataHeaders as $header): ?>
                        <th><?= htmlspecialchars($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dataRows as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= htmlspecialchars($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($dataRows)): ?>
                <tr>
                    <td colspan="<?= count($dataHeaders) ?>">لا توجد بيانات للعرض.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- معلومات إضافية -->
        <?php if (!empty($invoiceInfo) && is_array($invoiceInfo)): ?>
        <div class="info-block">
            <ul>
                <?php foreach ($invoiceInfo as $label => $val): ?>
                    <li><b><?= htmlspecialchars($label) ?>:</b> <?= htmlspecialchars($val) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>