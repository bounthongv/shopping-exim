<?php
session_start();
include "config.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

// ຍັງບໍ່ທັນຕັ້ງລະຫັດຜ່ານ → ບັງຄັບໄປຕັ້ງກ່ອນ
if (!empty($_SESSION['must_set_password'])) {
    header("Location: change_password.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Menu</title>
<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>

<div class="panel menu-panel">
    <!-- Header Section -->
    <div class="welcome-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px;">

        <!-- Logo and Welcome Text -->
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="Exim Logo Cars - Copy_1597743348.png" alt="Exim Logo" style="height: 45px;">
            <div class="welcome-text" style="text-align: left;">
                <h2 style="margin: 0 0 5px 0; font-size: 22px; color: #1e293b;">ຍິນດີຕ້ອນຮັບ</h2>
                <p style="margin: 0; color: #1e293b; font-size: 14px;">ລະບົບຕິດຕາມການຊື້ເບຍ Heineken</p>
            </div>
        </div>
    </div>

    <!-- User Info Row -->
    <div class="header-row">
        <div class="cell"><i class="bi bi-person-fill" style="margin-right: 15px;"></i><?php echo $_SESSION['customer_id']; ?></div>
        <div class="cell"><?php echo $_SESSION['customer_name']; ?></div>
    </div>

    <div class="menu-grid">
        
        <a class="menu-item" href="invoice_list.php">
            <span><i class="bi bi-receipt" style="width: 24px; font-size: 1.1em; margin-right: 15px;"></i>ລາຍການອິນວອຍ / Invoice List</span>
        </a>
        <a class="menu-item" href="contract_info.php">
            <span><i class="bi bi-file-earmark-text" style="width: 24px; font-size: 1.1em; margin-right: 15px;"></i>ຂໍ້ມູນສັນຍາ / Contract Information</span>
        </a>
        <a class="menu-item" href="purchase_summary.php">
            <span><i class="bi bi-pie-chart" style="width: 24px; font-size: 1.1em; margin-right: 15px;"></i>ລາຍງານຍອດຊື້ / Purchase Summary</span>
        </a>
        <a class="menu-item" href="rpm_return.php">
            <span><i class="bi bi-box-seam" style="width: 24px; font-size: 1.1em; margin-right: 15px;"></i>ລາຍງານສົ່ງລັງ / RPM Return</span>
        </a>
        <a class="menu-item" href="summary_report.php">
            <span><i class="bi bi-graph-up-arrow" style="width: 24px; font-size: 1.1em; margin-right: 15px;"></i>ລາຍງານສັງລວມ / Summary Report</span>
        </a>
        <a class="menu-item highlight" href="change_password.php">
            <span><i class="bi bi-key" style="width: 24px; font-size: 1.1em; margin-right: 15px;"></i>ປ່ຽນລະຫັດຜ່ານ / Change Password</span>
        </a>
        <a class="menu-item" href="shop_profile.php">
            <span><i class="bi bi-person-lines-fill" style="width: 24px; font-size: 1.1em; margin-right: 15px;"></i>ເບິ່ງຂໍ້ມູນຮ້ານຄ້າ / Shop Profile</span>
        </a>
    </div>

    <a class="logout-btn" href="logout.php">
        <span><i class="bi bi-box-arrow-right" style="width: 24px; font-size: 1.1em; margin-right: 15px;"></i>ອອກຈາກລະບົບ / Logout</span>
    </a>

</div>

</body>
</html>
