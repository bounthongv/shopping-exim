<?php
// ຕ້ອງ session_start() ກ່ອນ ຈຶ່ງຈະ session_destroy() ໄດ້
// (ຂອງເກົ່າບໍ່ມີ → ລຶບ session ບໍ່ອອກ → login ຄ້າງ)
session_start();

$_SESSION = [];

// ລຶບ cookie ຂອງ session ນຳ
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}

session_destroy();

header("Location: index.php");
exit;
