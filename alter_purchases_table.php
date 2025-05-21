<?php
// ملف تنفيذ أمر تعديل جدول المشتريات وإضافة حقول الضريبة والإجمالي مع الضريبة
$host = "localhost";
$user = "root";
$pass = "";
$db   = "rokood_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("خطأ في الاتصال: " . $conn->connect_error);
}

$sql = "ALTER TABLE purchases 
    ADD COLUMN vat_amount DECIMAL(12,2) NOT NULL DEFAULT 0, 
    ADD COLUMN total_with_vat DECIMAL(12,2) NOT NULL DEFAULT 0;";

if ($conn->query($sql) === TRUE) {
    echo "تم إضافة الحقول بنجاح.";
} else {
    echo "حدث خطأ: " . $conn->error;
}
$conn->close();
?>