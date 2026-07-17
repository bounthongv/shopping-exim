<?php
session_start();
include("config.php");

if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Get details from customer_import
$stmt = mysqli_prepare($con, "
    SELECT c.customer_name, i.* 
    FROM customers c 
    LEFT JOIN customer_import i ON c.customer_id = i.external_id 
    WHERE c.customer_id = ?
");
if($stmt){
    mysqli_stmt_bind_param($stmt, "s", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $shop = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$shop) {
    // Fallback if not found in import table
    $shop = [
        'customer_name' => $_SESSION['customer_name'],
        'external_id' => $customer_id,
        'outlet_name_la' => '-',
        'phone_number' => '-',
        'Province' => '-',
        'district' => '-',
        'village' => '-',
        'credit' => '-',
        'Debt_collection' => '-',
        'Number_of_days_overdue' => '-',
        'Contract_expiration_date' => '-'
    ];
}

$displayName = !empty($shop['outlet_name_la']) ? $shop['outlet_name_la'] : (!empty($shop['customer_name']) ? $shop['customer_name'] : '-');
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ໂປຣໄຟລ໌ຜູ້ໃຊ້ / User Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    * {
        box-sizing: border-box;
        font-family: 'Noto Sans Lao', sans-serif !important;
    }
    body {
        background-color: rgba(0,0,0,0.5); /* Modal background feel */
        margin: 0;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 100vh;
    }
    .profile-modal {
        background: #f8f9fa;
        width: 100%;
        max-width: 800px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        margin-top: 20px;
    }
    .modal-header {
        background-color: #0d6efd;
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .close-btn {
        color: white;
        text-decoration: none;
        font-size: 24px;
        line-height: 1;
    }
    .modal-body {
        padding: 30px;
        background-color: white;
    }
    
    .profile-top {
        text-align: center;
        margin-bottom: 30px;
    }
    .avatar-wrapper {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid #0d6efd;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin-bottom: 15px;
        background-color: #e9ecef;
    }
    .avatar-wrapper i {
        font-size: 60px;
        color: #6c757d;
    }
    .profile-name {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 5px 0;
        color: #333;
    }
    .profile-role {
        font-size: 14px;
        color: #666;
        margin: 0 0 10px 0;
    }
    .badge-role {
        background-color: #dc3545;
        color: white;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        display: inline-block;
    }

    .info-container {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .info-card {
        flex: 1;
        min-width: 300px;
        background: #fafafa;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 20px;
    }
    .info-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-top: 0;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }
    .info-row {
        display: flex;
        margin-bottom: 15px;
        font-size: 14px;
    }
    .info-label {
        width: 120px;
        color: #666;
    }
    .info-value {
        flex: 1;
        color: #000;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #198754;
        color: white;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 12px;
    }

    .modal-footer {
        padding: 15px 20px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
        background-color: white;
    }
    .btn-cancel {
        background-color: #dc3545;
        color: white;
        text-decoration: none;
        padding: 8px 20px;
        border-radius: 6px;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: 0.2s;
    }
    .btn-cancel:hover {
        background-color: #c82333;
    }

    @media (max-width: 768px) {
        .info-container {
            flex-direction: column;
        }
        .modal-body {
            padding: 15px;
        }
    }
</style>
</head>
<body>

<div class="profile-modal">
    <div class="modal-header">
        <h3><i class="bi bi-person-circle"></i> ໂປຣໄຟລ໌ລູກຄ້າ / Customer Profile</h3>
        <a href="menu.php" class="close-btn">&times;</a>
    </div>

    <div class="modal-body">
        <div class="profile-top">
            <div class="avatar-wrapper">
                <i class="bi bi-person-fill"></i>
            </div>
            <h2 class="profile-name"><?php echo $displayName; ?></h2>
            <p class="profile-role">ລູກຄ້າ / Customer</p>
            <div class="badge-role">ຜູ້ໃຊ້ງານລະບົບ</div>
        </div>

        <div class="info-container">
            <!-- Left Column: Account Info -->
            <div class="info-card">
                <h4 class="info-title"><i class="bi bi-person-fill" style="color: #0d6efd;"></i> ຂໍ້ມູນບັນຊີ</h4>
                
                <div class="info-row">
                    <div class="info-label">User ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer_id); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($shop['customer_name'] ?? '-'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ບົດບາດ</div>
                    <div class="info-value"><span class="badge-role">ລູກຄ້າ</span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ສະຖານະ</div>
                    <div class="info-value"><span class="status-active">ເຄື່ອນໄຫວ</span></div>
                </div>
            </div>

            <!-- Right Column: Shop Info -->
            <div class="info-card">
                <h4 class="info-title"><i class="bi bi-card-heading" style="color: #198754;"></i> ຂໍ້ມູນຮ້ານຄ້າ</h4>
                
                <div class="info-row">
                    <div class="info-label">ລະຫັດຮ້ານ</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer_id); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ຊື່ຮ້ານ</div>
                    <div class="info-value"><?php echo htmlspecialchars($shop['outlet_name_la'] ?? '-'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ເບີໂທ</div>
                    <div class="info-value"><?php echo htmlspecialchars($shop['phone_number'] ?? '-'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ບ້ານ</div>
                    <div class="info-value"><?php 
                        $village = !empty($shop['Village_LA']) ? $shop['Village_LA'] : (!empty($shop['village']) && !is_numeric(trim($shop['village'])) ? $shop['village'] : '-');
                        echo htmlspecialchars($village); 
                    ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ເມືອງ</div>
                    <div class="info-value"><?php echo htmlspecialchars(!empty($shop['district']) ? $shop['district'] : '-'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ແຂວງ</div>
                    <div class="info-value"><?php echo htmlspecialchars($shop['Province'] ?? '-'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ສິນເຊື່ອ</div>
                    <div class="info-value"><?php echo htmlspecialchars($shop['credit'] ?? '-'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <a href="menu.php" class="btn-cancel">
            <i class="bi bi-x"></i> ຍົກເລີກ
        </a>
    </div>
</div>

</body>
</html>
