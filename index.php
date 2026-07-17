<?php
require_once "config.php";


if (isset($_SESSION['customer_id'])) {
    header("Location: menu.php");
    exit;
}

$error = "";
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ເຂົ້າສູ່ລະບົບ / Login</title>
<!-- Google Fonts: Noto Sans Lao -->
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    * {
        box-sizing: border-box;
        font-family: 'Noto Sans Lao', sans-serif !important;
    }
    body {
        background-color: #f8f9fa;
        margin: 0;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }
    .login-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        width: 100%;
        max-width: 420px;
        overflow: hidden;
    }
    .login-header {
        background: linear-gradient(135deg, #9fbbe9ff 0%, #8ca2dfff 100%);
        padding: 40px 20px;
        text-align: center;
        position: relative;
        color: #ffffff;
        overflow: hidden;
    }
    /* Decorative circles for header */
    .login-header::before {
        content: '';
        position: absolute;
        top: -30px;
        left: -30px;
        width: 120px;
        height: 120px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }
    .login-header::after {
        content: '';
        position: absolute;
        bottom: -40px;
        right: -20px;
        width: 150px;
        height: 150px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }
    .logo-circle {
        width: 80px;
        height: 80px;
        background: #ffffff;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 16px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        position: relative;
        z-index: 1;
    }
    .logo-circle i {
        font-size: 36px;
        color: #6890e6ff;
    }
    .login-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }
    .login-header p {
        margin: 6px 0 0 0;
        font-size: 13px;
        color: #dbeafe;
        position: relative;
        z-index: 1;
    }
    .login-body {
        padding: 30px 24px;
    }
    .error-msg {
        background: #fee2e2;
        color: #dc2626;
        padding: 10px;
        border-radius: 8px;
        text-align: center;
        font-size: 14px;
        margin-bottom: 20px;
        border: 1px solid #fca5a5;
    }
    .input-group {
        margin-bottom: 20px;
    }
    .input-group label {
        display: block;
        font-size: 14px;
        color: #4b5563;
        margin-bottom: 8px;
        font-weight: 600;
    }
    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .input-wrapper i {
        position: absolute;
        left: 14px;
        color: #3b82f6;
        font-size: 16px;
    }
    .input-wrapper input {
        width: 100%;
        padding: 14px 14px 14px 40px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 15px;
        color: #1f2937;
        outline: none;
        transition: all 0.2s;
    }
    .input-wrapper input:focus {
        border-color: #6181b4ffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .input-wrapper input.readonly {
        background-color: #f3f4f6;
        color: #6b7280;
    }
    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, #3b82f6 0%, #738dd4ff 100%);
        color: #ffffff;
        border: none;
        padding: 15px;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
    .btn-submit:active {
        transform: translateY(2px);
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.2);
    }
</style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="logo-circle">
            <img src="Exim Logo Cars - Copy_1597743348.png" alt="Logo" style="max-width: 55px; max-height: 55px; border-radius: 5px;">
        </div>
        <h2>ລະບົບຕິດຕາມການຊື້ເບຍ Heineken</h2>
        <p>Customer Login Portal</p>
    </div>

    <form class="login-body" action="login_action.php" method="POST" autocomplete="off">
        
        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="input-group">
            <label for="customer_id">ຊື່ຜູ້ໃຊ້ (Username)</label>
            <div class="input-wrapper">
                <i class="bi bi-person-fill"></i>
                <input type="text" id="customer_id" name="customer_id" placeholder="ປ້ອນຊື່ຜູ້ໃຊ້" required autofocus>
            </div>
        </div>

        <div class="input-group">
            <label for="password">ລະຫັດຜ່ານ (Password)</label>
            <div class="input-wrapper">
                <i class="bi bi-lock-fill"></i>
                <input type="password" id="password" name="password" placeholder="ປ້ອນລະຫັດຜ່ານ" required>
            </div>
        </div>


        <div class="input-group" id="customer_name_group" style="display:none;">
            <label for="customer_name">ຊື່ລູກຄ້າ/ຊື່ຮ້ານ</label>
            <div class="input-wrapper">
                <i class="bi bi-person-lines-fill"></i>
                <input type="text" id="customer_name" name="customer_name" class="readonly" readonly tabindex="-1">
            </div>
        </div>

        <input type="hidden" id="phone" name="phone">
        <input type="hidden" id="address" name="address">

        <button type="submit" class="btn-submit">
            <i class="bi bi-box-arrow-in-right"></i> ເຂົ້າສູ່ລະບົບ
        </button>
    </form>
</div>

<script>

let typingTimer;
document.getElementById('customer_id').addEventListener('input', function () {
    clearTimeout(typingTimer);
    const id = this.value.trim();
    if (!id) {
        document.getElementById('customer_name_group').style.display = 'none';
        return;
    }
    typingTimer = setTimeout(() => {
        fetch('get_customer_info.php?customer_id=' + encodeURIComponent(id))
            .then(res => res.json())
            .then(data => {
                if (data.found) {
                    document.getElementById('customer_name_group').style.display = 'block';
                    document.getElementById('customer_name').value = data.customer_name || '';
                    document.getElementById('phone').value = data.phone || '';
                    document.getElementById('address').value = data.address || '';
                } else {
                    document.getElementById('customer_name_group').style.display = 'none';
                    document.getElementById('customer_name').value = '';
                }
            })
            .catch(() => {});
    }, 500);
});
</script>

</body>
</html>