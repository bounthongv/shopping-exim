<?php
session_start();
include "config.php";        // ໝາຍເຫດ: config.php ເຮັດ addslashes+strip_tags ໃສ່ $_POST
include "auth_helper.php";

$login_name = raw_post('customer_id');   // ຮັບໄດ້ທັງ username_invoice ແລະ customer_id
$password   = raw_post('password');

if ($login_name === "") {
    $_SESSION['login_error'] = "ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້";
    header("Location: index.php");
    exit;
}

/* ---------- ຫາລູກຄ້າ ----------
   1) ທຽບ username_invoice ກ່ອນ
   2) ຖ້າບໍ່ພົບ → ທຽບ customer_id ສະເພາະຄົນທີ່ຍັງບໍ່ທັນຕັ້ງ username (ລູກຄ້າເກົ່າ)
   ຖ້າຍັງບໍ່ທັນ run SQL → ຖອຍໄປໃຊ້ customer_id ຢ່າງດຽວ (ເວັບບໍ່ລົ້ມ)
*/
$user = null;
$has_col = has_username_column($con);

if ($has_col) {
    $stmt = mysqli_prepare($con, "
        SELECT customer_id, customer_name, password_invoice, username_invoice
        FROM customers
        WHERE username_invoice = ?
        LIMIT 1
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $login_name);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }
}

if (!$user) {
    $sql = $has_col
        ? "SELECT customer_id, customer_name, password_invoice, username_invoice
           FROM customers
           WHERE customer_id = ?
             AND (username_invoice IS NULL OR username_invoice = '')
           LIMIT 1"
        : "SELECT customer_id, customer_name, password_invoice
           FROM customers
           WHERE customer_id = ?
           LIMIT 1";
    $stmt = mysqli_prepare($con, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $login_name);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }
}

if (!$user) {
    $_SESSION['login_error'] = "ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານ ບໍ່ຖືກຕ້ອງ";
    header("Location: index.php");
    exit;
}

$stored_pw = $user['password_invoice'];
$has_password = trim((string)$stored_pw) !== '';

if ($has_password) {
    if (!pw_verify($password, $stored_pw)) {
        $_SESSION['login_error'] = "ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານ ບໍ່ຖືກຕ້ອງ";
        header("Location: index.php");
        exit;
    }
    // ຂໍ້ມູນເກົ່າທີ່ເປັນ plain text → ອັບເປັນ hash ໃຫ້ອັດຕະໂນມັດ
    if (pw_needs_upgrade($stored_pw)) {
        $new_hash = pw_hash($password);
        $up = mysqli_prepare($con, "UPDATE customers SET password_invoice = ? WHERE customer_id = ?");
        if ($up) {
            mysqli_stmt_bind_param($up, "ss", $new_hash, $user['customer_id']);
            mysqli_stmt_execute($up);
            mysqli_stmt_close($up);
        }
    }
}

session_regenerate_id(true);
$_SESSION['customer_id']   = $user['customer_id'];
$_SESSION['customer_name'] = $user['customer_name'];

if (!$has_password) {
    // ລູກຄ້າເກົ່າທີ່ຍັງບໍ່ທັນຕັ້ງລະຫັດ → ບັງຄັບໄປຕັ້ງກ່ອນ
    $_SESSION['must_set_password'] = true;
    header("Location: change_password.php");
    exit;
}

header("Location: menu.php");
exit;
