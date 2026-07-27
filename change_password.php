<?php
session_start();
include "config.php";        // ໝາຍເຫດ: config.php ເຮັດ addslashes+strip_tags ໃສ່ $_POST
include "auth_helper.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// ຕົວກັນ: run sql/add_username_invoice.sql ແລ້ວ ຫຼື ຍັງ
$has_col = has_username_column($con);

/* ---------- ດຶງຂໍ້ມູນລູກຄ້າ ---------- */
$shop = null;
$stmt = mysqli_prepare($con, "
    SELECT c.*, i.outlet_name_la, i.Village_LA, i.district, i.phone_number
    FROM customers c
    LEFT JOIN customer_import i ON c.customer_id = i.external_id
    WHERE c.customer_id = ?
    ORDER BY c.customer_id
    LIMIT 1
");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $customer_id);
    mysqli_stmt_execute($stmt);
    $shop = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}
if (!$shop) {
    $shop = [
        'customer_name'    => $_SESSION['customer_name'] ?? '',
        'phone_number'     => '-',
        'password_invoice' => '',
        'username_invoice' => '',
    ];
}

$stored_pw   = $shop['password_invoice'] ?? '';
$has_password = trim((string)$stored_pw) !== '';          // ຕັ້ງລະຫັດແລ້ວ ຫຼື ຍັງ
$cur_username = trim((string)($shop['username_invoice'] ?? ''));
if ($cur_username === '') $cur_username = $customer_id;   // ຍັງບໍ່ທັນຕັ້ງ → ໃຊ້ລະຫັດລູກຄ້າ

$message  = "";
$msg_type = "";

/* ---------- ບັນທຶກ ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_username = raw_post('new_username');
    $old_password = raw_post('old_password');
    $new_password = raw_post('new_password');
    $confirm      = raw_post('confirm_password');

    if ($has_col && !username_ok($new_username)) {
        $message  = "Username ຕ້ອງເປັນ ຕົວອັກສອນອັງກິດ / ຕົວເລກ / . _ - ຄວາມຍາວ 3-50 ຕົວ";
        $msg_type = "error";

    } elseif ($has_password && !pw_verify($old_password, $stored_pw)) {
        $message  = "ລະຫັດຜ່ານເກົ່າບໍ່ຖືກຕ້ອງ";
        $msg_type = "error";

    } elseif (mb_strlen($new_password) < 4) {
        $message  = "ລະຫັດຜ່ານໃໝ່ ຕ້ອງມີຢ່າງໜ້ອຍ 4 ຕົວອັກສອນ";
        $msg_type = "error";

    } elseif (!pw_charset_ok($new_password)) {
        $message  = "ລະຫັດຜ່ານໃຊ້ໄດ້ແຕ່ ຕົວອັກສອນອັງກິດ, ຕົວເລກ ແລະ ສັນຍາລັກທົ່ວໄປ (ຫ້າມ < >)";
        $msg_type = "error";

    } elseif ($new_password !== $confirm) {
        $message  = "ລະຫັດຜ່ານໃໝ່ ແລະ ການຢືນຢັນ ບໍ່ຕົງກັນ";
        $msg_type = "error";

    } else {
        /* Username ຊ້ຳກັບລູກຄ້າຄົນອື່ນບໍ່ (ທຽບທັງ username_invoice ແລະ customer_id) */
        $dup = false;
        if ($has_col) {
            $chk = mysqli_prepare($con, "
                SELECT customer_id FROM customers
                WHERE (username_invoice = ? OR customer_id = ?)
                  AND customer_id <> ?
                LIMIT 1
            ");
            if ($chk) {
                mysqli_stmt_bind_param($chk, "sss", $new_username, $new_username, $customer_id);
                mysqli_stmt_execute($chk);
                $dup = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
                mysqli_stmt_close($chk);
            }
        }

        if ($dup) {
            $message  = "Username \"$new_username\" ຖືກໃຊ້ແລ້ວ ກະລຸນາເລືອກໃໝ່";
            $msg_type = "error";
        } else {
            $hash = pw_hash($new_password);
            // customer_id ບໍ່ຖືກແຕະ → ອິນວອຍ / ຂໍ້ມູນຮ້ານ ຍັງເຊື່ອມຄືເກົ່າ
            // ຖ້າຍັງບໍ່ທັນ run SQL → ບັນທຶກແຕ່ password (ບໍ່ແຕະ username_invoice)
            if ($has_col) {
                $upd = mysqli_prepare($con, "
                    UPDATE customers
                    SET username_invoice = ?, password_invoice = ?
                    WHERE customer_id = ?
                ");
                if ($upd) mysqli_stmt_bind_param($upd, "sss", $new_username, $hash, $customer_id);
            } else {
                $upd = mysqli_prepare($con, "
                    UPDATE customers
                    SET password_invoice = ?
                    WHERE customer_id = ?
                ");
                if ($upd) mysqli_stmt_bind_param($upd, "ss", $hash, $customer_id);
            }

            if ($upd) {
                if (mysqli_stmt_execute($upd)) {
                    mysqli_stmt_close($upd);
                    if ($has_col) $cur_username = $new_username;
                    $stored_pw    = $hash;
                    $has_password = true;
                    unset($_SESSION['must_set_password']);
                    $message  = $has_col
                        ? "ບັນທຶກ Username ແລະ ລະຫັດຜ່ານ ສຳເລັດແລ້ວ!"
                        : "ບັນທຶກລະຫັດຜ່ານ ສຳເລັດແລ້ວ! (Username ຍັງປ່ຽນບໍ່ໄດ້ເທື່ອ)";
                    $msg_type = "success";
                } else {
                    $message  = "ເກີດຂໍ້ຜິດພາດ: " . mysqli_stmt_error($upd);
                    $msg_type = "error";
                    mysqli_stmt_close($upd);
                }
            }
        }
    }
}

$first_time = !$has_password;   // ຍັງບໍ່ທັນຕັ້ງລະຫັດ → ບໍ່ຕ້ອງຖາມລະຫັດເກົ່າ

// ຖືກບັງຄັບມາຕັ້ງລະຫັດ: menu.php ຈະເດັ້ງກັບມາໜ້ານີ້ ສະນັ້ນປຸ່ມອອກຕ້ອງໄປ logout
// ບໍ່ດັ່ງນັ້ນຈະຕິດວົງຈົນ ອອກບໍ່ໄດ້
$forced   = !empty($_SESSION['must_set_password']);
$exit_url = $forced ? 'logout.php' : 'menu.php';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ການປ່ຽນລະຫັດຜ່ານ</title>
<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
.customer-header-boxes { display: flex; gap: 15px; margin-bottom: 20px; }
.customer-box {
    flex: 1; background-color: #f0f7ff; border: 1px solid #dbeafe;
    border-radius: 12px; padding: 12px 15px;
    display: flex; flex-direction: column; justify-content: center;
}
.customer-box .top-line {
    font-weight: 600; color: #1e3a8a; font-size: 15px; margin-bottom: 4px;
    display: flex; align-items: center; gap: 8px; justify-content: center;
}
.customer-box .bottom-line { color: #475569; font-size: 14px; text-align: center; }
.full-panel { max-width: 600px; }
.form-section {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 14px; padding: 20px; margin-bottom: 16px;
}
.form-section-title {
    font-size: 15px; font-weight: 700; color: #1e3a8a; margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
    padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;
}
.action-row { display: flex; gap: 10px; margin-top: 20px; }
.btn-secondary {
    background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5;
    font-size: 16px; font-weight: 600; padding: 14px; border-radius: 8px;
    cursor: pointer; flex: 1; transition: all 0.2s; text-decoration: none;
    text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-secondary:hover { background: #fee2e2; }
.btn-primary { flex: 2; }
.alert-success, .alert-error {
    border-radius: 10px; padding: 14px 16px; margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px; font-size: 15px;
}
.alert-success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
.alert-error   { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; }
.alert-info {
    background: #eff6ff; border: 1px solid #93c5fd; color: #1e40af;
    border-radius: 10px; padding: 14px 16px; margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px; font-size: 15px;
}
.password-toggle { position: relative; }
.password-toggle input { padding-right: 44px; }
.toggle-eye {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    cursor: pointer; color: #94a3b8; font-size: 18px; user-select: none;
}
.toggle-eye:hover { color: #1e3a8a; }
.hint-text { font-size: 12px; color: #94a3b8; margin-top: 4px; }
</style>
</head>
<body>
<div class="panel full-panel">

    <!-- ກ່ອງຂໍ້ມູນລູກຄ້າ -->
    <div class="customer-header-boxes">
        <div class="customer-box">
            <div class="top-line"><i class="bi bi-person-fill"></i> <?php echo htmlspecialchars($cur_username); ?></div>
            <div class="bottom-line"><?php echo htmlspecialchars($shop['phone_number'] ?? '-'); ?></div>
        </div>
        <div class="customer-box">
            <div class="top-line"><?php echo htmlspecialchars(!empty($shop['outlet_name_la']) ? $shop['outlet_name_la'] : ($shop['customer_name'] ?? '-')); ?></div>
            <div class="bottom-line"><?php
                $village  = !empty($shop['Village_LA']) ? $shop['Village_LA'] : '';
                $district = !empty($shop['district'])   ? $shop['district']   : '';
                if ($village && $district)  $location = $village . ' - ' . $district;
                elseif ($village)           $location = $village;
                elseif ($district)          $location = $district;
                else                        $location = '-';
                echo htmlspecialchars($location);
            ?></div>
        </div>
    </div>

    <!-- ຫົວຂໍ້ -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
        <h2 style="margin: 0; font-size: 22px; font-weight: 700; color: #1e3a8a;">
            <i class="bi bi-key-fill" style="color: #3b82f6; margin-right: 6px;"></i> ການປ່ຽນລະຫັດຜ່ານ
        </h2>
        <button type="button" class="btn-back" onclick="window.location.href='<?php echo $exit_url; ?>'">
            <?php if ($forced): ?>
                <i class="bi bi-box-arrow-right"></i> ອອກຈາກລະບົບ
            <?php else: ?>
                <i class="bi bi-arrow-left"></i> ກັບຄືນ
            <?php endif; ?>
        </button>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $msg_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <i class="bi bi-<?php echo $msg_type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$has_col): ?>
        <div class="alert-error">
            <i class="bi bi-database-exclamation"></i>
            ຍັງບໍ່ທັນໄດ້ run <b>sql/add_username_invoice.sql</b> ໃນ phpMyAdmin
            ຈຶ່ງຕັ້ງໄດ້ແຕ່ລະຫັດຜ່ານ (Username ຍັງປ່ຽນບໍ່ໄດ້) — ກະລຸນາແຈ້ງຜູ້ດູແລລະບົບ
        </div>
    <?php endif; ?>

    <?php if ($first_time): ?>
        <div class="alert-info">
            <i class="bi bi-info-circle-fill"></i>
            ທ່ານຍັງບໍ່ທັນຕັ້ງລະຫັດຜ່ານ ກະລຸນາຕັ້ງ<?php echo $has_col ? ' Username ແລະ' : ''; ?> ລະຫັດຜ່ານ ຂອງທ່ານ
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <!-- Username -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-person-badge-fill"></i> ຊື່ຜູ້ໃຊ້ (Username)
            </div>
            <div class="field">
                <label>ລະຫັດລູກຄ້າ (ບໍ່ສາມາດປ່ຽນໄດ້)</label>
                <input type="text" value="<?php echo htmlspecialchars($customer_id); ?>" readonly
                       style="background: #f1f5f9; color: #64748b;">
                <div class="hint-text">ໃຊ້ເຊື່ອມກັບ ອິນວອຍ ແລະ ຂໍ້ມູນຮ້ານ ຂອງທ່ານ</div>
            </div>
            <div class="field">
                <label>Username <?php if ($has_col): ?><span style="color: red;">*</span><?php endif; ?></label>
                <input type="text" name="new_username"
                       value="<?php echo htmlspecialchars($_POST['new_username'] ?? $cur_username); ?>"
                       placeholder="ພິມ Username..."
                       <?php if ($has_col): ?>required<?php else: ?>readonly style="background: #f1f5f9; color: #64748b;"<?php endif; ?>>
                <div class="hint-text">
                    <?php echo $has_col
                        ? 'ຕົວອັກສອນອັງກິດ / ຕົວເລກ / . _ - (3-50 ຕົວ)'
                        : 'ຍັງປ່ຽນບໍ່ໄດ້ເທື່ອ (ລໍຖ້າຜູ້ດູແລລະບົບ run SQL)'; ?>
                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-lock-fill"></i> ລະຫັດຜ່ານ (Password)
            </div>

            <?php if (!$first_time): ?>
            <div class="field">
                <label>ລະຫັດຜ່ານ (ເກົ່າ) <span style="color: red;">*</span></label>
                <div class="password-toggle">
                    <input type="password" name="old_password" id="old_password"
                           placeholder="ພິມລະຫັດຜ່ານເກົ່າ..." required>
                    <i class="bi bi-eye-slash toggle-eye" onclick="togglePass('old_password', this)"></i>
                </div>
            </div>
            <?php endif; ?>

            <div class="field">
                <label>ລະຫັດຜ່ານ (ໃໝ່) <span style="color: red;">*</span></label>
                <div class="password-toggle">
                    <input type="password" name="new_password" id="new_password"
                           placeholder="ພິມລະຫັດຜ່ານໃໝ່..." required>
                    <i class="bi bi-eye-slash toggle-eye" onclick="togglePass('new_password', this)"></i>
                </div>
                <div class="hint-text">ຢ່າງໜ້ອຍ 4 ຕົວອັກສອນ (ຫ້າມ < >)</div>
            </div>

            <div class="field">
                <label>ຢືນຢັນລະຫັດຜ່ານໃໝ່ <span style="color: red;">*</span></label>
                <div class="password-toggle">
                    <input type="password" name="confirm_password" id="confirm_password"
                           placeholder="ພິມລະຫັດຜ່ານໃໝ່ອີກຄັ້ງ..." required>
                    <i class="bi bi-eye-slash toggle-eye" onclick="togglePass('confirm_password', this)"></i>
                </div>
            </div>
        </div>

        <div class="action-row">
            <button type="submit" class="btn-primary">
                <i class="bi bi-floppy-fill"></i> ບັນທຶກ
            </button>
            <a href="<?php echo $exit_url; ?>" class="btn-secondary">
                <?php if ($forced): ?>
                    <i class="bi bi-box-arrow-right"></i> ອອກຈາກລະບົບ
                <?php else: ?>
                    <i class="bi bi-x-circle"></i> ຍົກເລີກ
                <?php endif; ?>
            </a>
        </div>

    </form>
</div>

<script>
function togglePass(fieldId, icon) {
    const input = document.getElementById(fieldId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    }
}
</script>
</body>
</html>
