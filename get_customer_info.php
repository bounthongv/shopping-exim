<?php
require_once "config.php";
require_once "auth_helper.php";
header("Content-Type: application/json; charset=utf-8");

// stripslashes: config.php ເຮັດ addslashes ໃສ່ $_GET ໃຫ້ແລ້ວ
$login_name = isset($_GET['customer_id']) ? stripslashes(trim($_GET['customer_id'])) : "";

$response = ["found" => false];

if ($login_name !== "") {
    // ຮັບໄດ້ທັງ username_invoice ແລະ customer_id (ຄືກັນກັບ login_action.php)
    // ຖ້າຍັງບໍ່ທັນ run SQL → ຖອຍໄປໃຊ້ customer_id ຢ່າງດຽວ
    $has_col = has_username_column($con);

    $sql = $has_col
        ? "SELECT c.customer_name, i.outlet_name_la
           FROM customers c
           LEFT JOIN customer_import i ON c.customer_id = i.external_id
           WHERE c.username_invoice = ?
              OR (c.customer_id = ? AND (c.username_invoice IS NULL OR c.username_invoice = ''))
           LIMIT 1"
        : "SELECT c.customer_name, i.outlet_name_la
           FROM customers c
           LEFT JOIN customer_import i ON c.customer_id = i.external_id
           WHERE c.customer_id = ?
           LIMIT 1";

    $row  = null;
    $stmt = mysqli_prepare($con, $sql);
    if ($stmt) {
        if ($has_col) mysqli_stmt_bind_param($stmt, "ss", $login_name, $login_name);
        else          mysqli_stmt_bind_param($stmt, "s",  $login_name);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }

    if ($row) {
        $display_name = !empty($row['outlet_name_la']) ? $row['outlet_name_la'] : (!empty($row['customer_name']) ? $row['customer_name'] : '-');
        $response = [
            "found" => true,
            "customer_name" => $display_name
        ];
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
