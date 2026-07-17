<?php
require_once "config.php";
if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<title>ວາຍງານຍອດຊື້ / Purchase Summary</title>
<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="panel">
    <div class="header-row">
        <div class="cell" style="width:100%;">ວາຍງານຍອດຊື້ / Purchase Summary</div>
    </div>
    <p style="color:#fff; text-align:center;">(หน้านี้ยังไม่มีข้อมูล - รอเชื่อมต่อ query จริง)</p>
    <a class="menu-item" href="menu.php" style="text-align:center;">&larr; ກັບຄືນໄປໜ້າເມນູ</a>
</div>
</body>
</html>
