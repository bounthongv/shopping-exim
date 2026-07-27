<?php
/**
 * auth_helper.php
 * ຟັງຊັນຊ່ວຍສຳລັບ Username / Password ຂອງລູກຄ້າ (invoice portal)
 * ໃຊ້ຮ່ວມກັນລະຫວ່າງ login_action.php ແລະ change_password.php
 */

/**
 * config.php ເຮັດ addslashes() + strip_tags() ໃສ່ $_POST ທຸກຕົວອັດຕະໂນມັດ.
 * password ຕ້ອງໄດ້ຄ່າດິບຄືນ ບໍ່ດັ່ງນັ້ນ hash ຈະບໍ່ຕົງກັນ.
 * addslashes ແກ້ຄືນໄດ້ດ້ວຍ stripslashes. strip_tags ແກ້ຄືນບໍ່ໄດ້
 * ເລີຍຕ້ອງຫ້າມ < > ໃນ password (ເບິ່ງ pw_charset_ok).
 */
function raw_post($key) {
    $v = isset($_POST[$key]) ? $_POST[$key] : '';
    return stripslashes(trim($v));
}

/**
 * ກວດ password ທຽບກັບຄ່າທີ່ເກັບໄວ້ໃນ DB.
 * ຮອງຮັບຂໍ້ມູນເກົ່າທີ່ເປັນ plain text (ຈາກຟອມລຸ້ນກ່ອນ) ນຳ.
 */
function pw_verify($input, $stored) {
    if ($stored === null || $stored === '') return false;
    $info = password_get_info($stored);
    if (!empty($info['algo'])) {
        return password_verify($input, $stored);   // hash ປົກກະຕິ
    }
    return hash_equals((string)$stored, (string)$input); // ຂໍ້ມູນເກົ່າ plain text
}

/** ຄ່າທີ່ເກັບໄວ້ຍັງເປັນ plain text ບໍ່ (ຕ້ອງ upgrade ເປັນ hash) */
function pw_needs_upgrade($stored) {
    if ($stored === null || $stored === '') return false;
    $info = password_get_info($stored);
    return empty($info['algo']);
}

function pw_hash($plain) {
    return password_hash($plain, PASSWORD_DEFAULT);
}

/**
 * ຫ້າມ < > ເພາະ strip_tags() ໃນ config.php ຈະກິນຖິ້ມ ແລ້ວແກ້ຄືນບໍ່ໄດ້.
 * ນອກນັ້ນອະນຸຍາດ ຕົວອັກສອນອັງກິດ, ຕົວເລກ ແລະ ສັນຍາລັກທົ່ວໄປ.
 */
function pw_charset_ok($plain) {
    return (bool)preg_match('/^[A-Za-z0-9!@#$%^&*()_\-+=.,?:;\[\]{}\/|~`\'"\\\\ ]+$/', $plain);
}

/** Username: ຕົວອັກສອນອັງກິດ, ຕົວເລກ, . _ - ຄວາມຍາວ 3-50 */
function username_ok($u) {
    return (bool)preg_match('/^[A-Za-z0-9._-]{3,50}$/', $u);
}

/**
 * ຕົວກັນ: ກວດວ່າ run sql/add_username_invoice.sql ແລ້ວ ຫຼື ຍັງ.
 * ຖ້າຍັງບໍ່ທັນ run → ລະບົບຖອຍໄປໃຊ້ວິທີເກົ່າ (login ດ້ວຍ customer_id)
 * ແທນທີ່ຈະລົ້ມທັງໝົດ. ກວດຄັ້ງດຽວຕໍ່ 1 request.
 */
function has_username_column($con) {
    static $cached = null;
    if ($cached !== null) return $cached;
    $res = mysqli_query($con, "SHOW COLUMNS FROM `customers` LIKE 'username_invoice'");
    $cached = ($res && mysqli_num_rows($res) > 0);
    return $cached;
}
