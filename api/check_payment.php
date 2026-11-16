<?php
session_start();
require_once "../config.php";   // เชื่อมฐานข้อมูล ($con)

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Vientiane');

function out($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$customer_id = isset($_SESSION['customer_id']) ? (string)$_SESSION['customer_id'] : '';
if ($customer_id === '') {
    out(["status" => "error", "message" => "ກະລຸນາເຂົ້າສູ່ລະບົບ"]);
}
session_write_close();   // ປ່ອຍ session ທັນທີ ເພາະໜ້າເວັບເອີ້ນໄຟລ໌ນີ້ທຸກ 3 ວິນາທີ

$bill_number = isset($_POST['bill_number']) ? trim((string)$_POST['bill_number']) : '';
if ($bill_number === '' || strlen($bill_number) > 32) {
    out(["status" => "error", "message" => "bill_number ບໍ່ຖືກຕ້ອງ"]);
}

try {
    // ກວດ customer_id ນຳ ເພື່ອໃຫ້ລູກຄ້າເຫັນສະຖານະໄດ້ສະເພາະລາຍການຂອງຕົນເອງ
    $st = mysqli_prepare($con, "SELECT status, amount, paid_at FROM payments
                                WHERE bill_number = ? AND customer_id = ? LIMIT 1");
    if (!$st) throw new Exception("prepare failed: " . mysqli_error($con));
    mysqli_stmt_bind_param($st, "ss", $bill_number, $customer_id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);

    if (!$row) {
        out(["status" => "error", "message" => "ບໍ່ພົບລາຍການ"]);
    }

    // ສະຖານະອ່ານຈາກຖານຂໍ້ມູນຢ່າງດຽວ ໜ້າເວັບບໍ່ມີສິດປ່ຽນເປັນ paid
    out([
        "status" => "success",
        "data"   => [
            "payment_status" => $row['status'],      // pending | paid | failed | expired
            "amount"         => (int)$row['amount'],
            "paid_at"        => $row['paid_at'],
        ],
    ]);

} catch (Throwable $e) {
    error_log("check_payment exception: " . $e->getMessage());
    out(["status" => "error", "message" => "ເກີດຂໍ້ຜິດພາດ"]);
}