function printSectionAsReceipt(htmlContent) {
    var content = `
        <link rel="stylesheet" href="receipt-print.css" media="print">
        <div class="receipt-body" style="font-family:'Cairo',Arial,Tahoma,sans-serif;direction:rtl;padding:10px;">
            ${htmlContent}
        </div>
    `;
    var myWindow = window.open("", "Print", "width=350,height=600");
    myWindow.document.write(content);
    myWindow.document.close();
    myWindow.focus();
    myWindow.print();
}