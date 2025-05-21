function printPurchaseFormPDF() {
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
        <style>
            @media print {
                html, body {
                    width: 80mm !important;
                    min-width: 80mm !important;
                    max-width: 80mm !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    direction: rtl;
                    background: #fff !important;
                }
                table {
                    width: 100% !important;
                    max-width: 80mm !important;
                }
            }
        </style>
        <div style="font-family:'Cairo',Arial,Tahoma,sans-serif;direction:rtl;padding:10px;">
            <h2 style="color:#304ffe;font-size:20px;text-align:center;">فاتورة شراء بيض</h2>
            <table style="width:100%;border:1px solid #304ffe;background:#fff;border-radius:10px;box-shadow:0 2px 10px #e0e0e0;">
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