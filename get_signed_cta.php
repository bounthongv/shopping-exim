<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['customer_id'])) {
    http_response_code(403);
    exit;
}

$customer_id = $_SESSION['customer_id'];

$file = '';
$stmt = mysqli_prepare($con, "SELECT File_CTA FROM tb_cta WHERE Customer_ID = ? ORDER BY Id DESC LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $file = $row['File_CTA'] ?? '';
    }
    mysqli_stmt_close($stmt);
}

if ($file === '') {
    http_response_code(404);
    exit;
}

// File_CTA ແມ່ນຊື່ໄຟລ໌ທີ່ລະບົບສ້າງເອງ (cta_<Id>_<time>.<ext>) ຈາກ exim/admin/update_cta.php
// ແຕ່ຍັງ basename() ໄວ້ເພື່ອກັນ path traversal ຖ້າຂໍ້ມູນຜິດປົກກະຕິ
$file = basename($file);
$path = __DIR__ . "/../exim/admin/pdf_file/" . $file;

if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'pdf'  => 'application/pdf',
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . $file . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($path);
exit;
