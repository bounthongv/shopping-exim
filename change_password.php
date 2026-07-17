<?php
session_start();
include "config.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];
$shop = null;

$stmt = mysqli_prepare($con, "
    SELECT c.customer_name, c.customer_id, c.password_invoice, i.outlet_name_la, i.Village_LA, i.district, i.phone_number
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
    $shop = ['customer_name' => $_SESSION['customer_name'] ?? '', 'phone_number' => '-'];
}

$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username    = trim($_POST['new_username']    ?? "");
    $new_password    = trim($_POST['new_password']    ?? "");
    $confirm_password = trim($_POST['confirm_password'] ?? "");

    if ($new_username === "") {
        $message  = "ກະລຸນາປ້ອນ Username";
        $msg_type = "error";
    } elseif ($new_password === "" || strlen($new_password) < 4) {
        $message  = "Password ຕ້ອງມີຢ່າງໜ້ອຍ 4 ຕົວອັກສອນ";
        $msg_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message  = "Password ໃໝ່ ແລະ ຢືນຢັນ Password ບໍ່ຕົງກັນ";
        $msg_type = "error";
    } else {
        $new_username_esc = mysqli_real_escape_string($con, $new_username);
        $new_password_esc = mysqli_real_escape_string($con, $new_password);

        // Check if new username already taken (by another customer)
        if ($new_username !== $customer_id) {
            $check = mysqli_query($con, "SELECT customer_id FROM customers WHERE customer_id='$new_username_esc' LIMIT 1");
            if ($check && mysqli_num_rows($check) > 0) {
                $message  = "Username '$new_username' ຖືກໃຊ້ແລ້ວ ກະລຸນາເລືອກໃໝ່";
                $msg_type = "error";
            }
        }

        if ($msg_type !== "error") {
            // Update password_invoice and customer_id (username)
            $current_id_esc = mysqli_real_escape_string($con, $customer_id);
            $update_sql = "UPDATE customers SET password_invoice='$new_password_esc', customer_id='$new_username_esc' WHERE customer_id='$current_id_esc'";
            if (mysqli_query($con, $update_sql)) {
                // Update session
                $_SESSION['customer_id'] = $new_username;
                $message  = "ບັນທຶກ Username ແລະ Password ສຳເລັດແລ້ວ!";
                $msg_type = "success";
                $customer_id = $new_username; // Update local var
            } else {
                $message  = "ເກີດຂໍ້ຜິດພາດ: " . mysqli_error($con);
                $msg_type = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ປ່ຽນ Username & Password</title>
<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
.customer-header-boxes {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}
.customer-box {
    flex: 1;
    background-color: #f0f7ff;
    border: 1px solid #dbeafe;
    border-radius: 12px;
    padding: 12px 15px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.customer-box .top-line {
    font-weight: 600;
    color: #1e3a8a;
    font-size: 15px;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
}
.customer-box .bottom-line {
    color: #475569;
    font-size: 14px;
    text-align: center;
}
.full-panel { max-width: 600px; }
.form-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 16px;
}
.form-section-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e3a8a;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e2e8f0;
}
.action-row {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}
.btn-secondary {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fca5a5;
    font-size: 16px;
    font-weight: 600;
    padding: 14px;
    border-radius: 8px;
    cursor: pointer;
    flex: 1;
    transition: all 0.2s;
    text-decoration: none;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.btn-secondary:hover { background: #fee2e2; }
.btn-primary { flex: 2; }
.alert-success {
    background: #f0fdf4;
    border: 1px solid #86efac;
    color: #166534;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
}
.alert-error {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #dc2626;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
}
.password-toggle {
    position: relative;
}
.password-toggle input {
    padding-right: 44px;
}
.toggle-eye {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #94a3b8;
    font-size: 18px;
    user-select: none;
}
.toggle-eye:hover { color: #1e3a8a; }
.hint-text {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
}
</style>
</head>
<body>
<div class="panel full-panel">

    <!-- Customer Info Header Boxes -->
    <div class="customer-header-boxes">
        <div class="customer-box">
            <div class="top-line"><i class="bi bi-person-fill"></i> <?php echo htmlspecialchars($customer_id); ?></div>
            <div class="bottom-line"><?php echo htmlspecialchars($shop['phone_number'] ?? '-'); ?></div>
        </div>
        <div class="customer-box">
            <div class="top-line"><?php echo htmlspecialchars(!empty($shop['outlet_name_la']) ? $shop['outlet_name_la'] : ($shop['customer_name'] ?? '-')); ?></div>
            <div class="bottom-line"><?php 
                $village = !empty($shop['Village_LA']) ? $shop['Village_LA'] : '';
                $district = !empty($shop['district']) ? $shop['district'] : '';
                if ($village && $district) $location = $village . ' - ' . $district;
                elseif ($village) $location = $village;
                elseif ($district) $location = $district;
                else $location = '-';
                echo htmlspecialchars($location);
            ?></div>
        </div>
    </div>

    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
        <h2 style="margin: 0; font-size: 22px; font-weight: 700; color: #1e3a8a;">
            <i class="bi bi-key-fill" style="color: #3b82f6; margin-right: 6px;"></i> ປ່ຽນ Username & Password
        </h2>
        <button type="button" class="btn-back" onclick="window.location.href='menu.php'">
            <i class="bi bi-arrow-left"></i> ກັບຄືນ
        </button>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $msg_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <i class="bi bi-<?php echo $msg_type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <!-- Username Section -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-person-badge-fill"></i> ຕັ້ງ Username ໃໝ່
            </div>
            <div class="field">
                <label>Username ປັດຈຸບັນ</label>
                <input type="text" value="<?php echo htmlspecialchars($customer_id); ?>" readonly style="background: #f1f5f9; color: #64748b;">
            </div>
            <div class="field">
                <label>Username ໃໝ່ <span style="color: red;">*</span></label>
                <input type="text" name="new_username" 
                       value="<?php echo htmlspecialchars($_POST['new_username'] ?? $customer_id); ?>"
                       placeholder="ພິມ Username ໃໝ່..." required>
                <div class="hint-text">ສາມາດໃຊ້ຕົວເລກ ຫຼື ຕົວໜັງສືພາສາອັງກິດ</div>
            </div>
        </div>

        <!-- Password Section -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-lock-fill"></i> ຕັ້ງ Password ໃໝ່
            </div>
            <div class="field">
                <label>Password ໃໝ່ <span style="color: red;">*</span></label>
                <div class="password-toggle">
                    <input type="password" name="new_password" id="new_password"
                           placeholder="ພິມ Password ໃໝ່..." required>
                    <i class="bi bi-eye-slash toggle-eye" onclick="togglePass('new_password', this)"></i>
                </div>
                <div class="hint-text">ຢ່າງໜ້ອຍ 4 ຕົວອັກສອນ</div>
            </div>
            <div class="field">
                <label>ຢືນຢັນ Password <span style="color: red;">*</span></label>
                <div class="password-toggle">
                    <input type="password" name="confirm_password" id="confirm_password"
                           placeholder="ພິມ Password ໃໝ່ອີກຄັ້ງ..." required>
                    <i class="bi bi-eye-slash toggle-eye" onclick="togglePass('confirm_password', this)"></i>
                </div>
            </div>
        </div>

        <div class="action-row">
            <button type="submit" class="btn-primary">
                <i class="bi bi-floppy-fill"></i> ບັນທຶກ
            </button>
            <a href="menu.php" class="btn-secondary">
                <i class="bi bi-x-circle"></i> ຍົກເລີກ
            </a>
        </div>

    </form>

</div>

<script>
function togglePass(fieldId, icon) {
    const input = document.getElementById(fieldId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    }
}
</script>

</body>
</html>
