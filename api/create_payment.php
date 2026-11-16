<?php
session_start();
require_once "../config.php";       // เชื่อมฐานข้อมูล ($con)
require_once "../config_api.php";   // ค่าของ API ธนาคาร

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Vientiane');

function out($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// ສ້າງ GUID (UUID v4) ສຳລັບ requestId
function make_guid() {
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

// ອ່ານຄວາມຍາວຂອງ tag ລະດັບນອກສຸດໃນ QR (EMV: tag 2 ຕົວ + ຄວາມຍາວ 2 ຕົວ + ຂໍ້ມູນ)
function emv_tag_length($qr, $wantTag) {
    $i = 0;
    $n = strlen($qr);
    while ($i + 4 <= $n) {
        $tag = substr($qr, $i, 2);
        $len = (int)substr($qr, $i + 2, 2);
        if ($tag === $wantTag) return $len;
        $i += 4 + $len;
    }
    return null;
}

// ບັນທຶກວ່າລາຍການນີ້ failed
function mark_failed($con, $bill_number, $code, $msg) {
    $code = substr((string)$code, 0, 10);
    $msg  = substr((string)$msg, 0, 255);
    $st = mysqli_prepare($con, "UPDATE payments SET status = 'failed', bank_code = ?, bank_message = ? WHERE bill_number = ?");
    mysqli_stmt_bind_param($st, "sss", $code, $msg, $bill_number);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
}

// ບັນທຶກຄຳຂໍ + ຄຳຕອບດິບຂອງທະນາຄານ ໄວ້ໃນແຖວ payments ເພື່ອໄວ້ກວດສາເຫດ
// (ຖ້າບັນທຶກບໍ່ສຳເລັດ ຈະບໍ່ກະທົບການສ້າງ QR)
function save_bank_debug($con, $bill_number, $http, $res, $curl_err, $body) {
    try {
        if ($res === false) {
            $resp_text = 'CURL ERROR: ' . $curl_err;
        } elseif (trim((string)$res) === '') {
            $resp_text = '(empty body)';
        } else {
            $resp_text = substr((string)$res, 0, 2000);
        }
        $req_text = substr(json_encode($body, JSON_UNESCAPED_UNICODE), 0, 2000);
        $http     = (int)$http;

        $st = mysqli_prepare($con, "UPDATE payments SET bank_http = ?, bank_response = ?, bank_request = ? WHERE bill_number = ?");
        mysqli_stmt_bind_param($st, "isss", $http, $resp_text, $req_text, $bill_number);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    } catch (Throwable $e) {
        error_log("save_bank_debug failed: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(["status" => "error", "message" => "Method not allowed"]);
}

$customer_id = isset($_SESSION['customer_id']) ? (string)$_SESSION['customer_id'] : '';
if ($customer_id === '') {
    out(["status" => "error", "message" => "ກະລຸນາເຂົ້າສູ່ລະບົບ"]);
}
session_write_close();

try {
    $now = date('Y-m-d H:i:s');

    // ---------- 0) ປັບ pending ທີ່ໝົດເວລາແລ້ວຂອງລູກຄ້ານີ້ ເປັນ expired ----------
    $ex = mysqli_prepare($con, "UPDATE payments SET status = 'expired'
                                WHERE customer_id = ? AND status = 'pending' AND expires_at < ?");
    mysqli_stmt_bind_param($ex, "ss", $customer_id, $now);
    mysqli_stmt_execute($ex);
    mysqli_stmt_close($ex);

    // ---------- 1) ອ່ານບິນທີ່ເລືອກ (sale_ids) ----------
    $sale_ids = [];
    if (isset($_POST['sale_ids'])) {
        $raw = is_array($_POST['sale_ids']) ? $_POST['sale_ids'] : explode(',', (string)$_POST['sale_ids']);
        $seen = [];
        foreach ($raw as $s) {
            $s = trim((string)$s);
            if ($s !== '') $seen[$s] = true;
        }
        $sale_ids = array_keys($seen);
    }
    if (count($sale_ids) > 100) {
        out(["status" => "error", "message" => "ເລືອກໄດ້ສູງສຸດ 100 ບິນຕໍ່ຄັ້ງ"]);
    }

    // ---------- 2) ອ່ານຕົວກອງ (ໃຊ້ເມື່ອບໍ່ໄດ້ເລືອກບິນ) ----------
    $from_date = isset($_POST['from_date']) ? trim($_POST['from_date']) : '';
    $to_date   = isset($_POST['to_date'])   ? trim($_POST['to_date'])   : '';
    $search    = isset($_POST['search'])    ? trim($_POST['search'])    : '';

    $date_ok = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($date_ok, $from_date) || !preg_match($date_ok, $to_date)) {
        $from_date = '';
        $to_date   = '';
    }

    // ---------- 3) ຄິດຍອດຈາກຖານຂໍ້ມູນ (ບໍ່ຮັບຍອດຈາກໜ້າເວັບ) ----------
    // ເງື່ອນໄຂ "ຈ່າຍແລ້ວ" ຕາມໜ້າ invoice (status = 2 ຫຼື status_payment = 2/3)
    // remain: ສົມມຸດວ່າເປັນຍອດຄ້າງລະດັບບິນ ຈຶ່ງໃຊ້ MAX — ກະລຸນາກວດໃຫ້ຕົງກັບຂໍ້ມູນຈິງ
    $sql = "SELECT sale_id, MAX(remain) AS remain
            FROM product_sale
            WHERE customer_id = ?
              AND COALESCE(status, '') <> '2'
              AND COALESCE(status_payment, '') NOT IN ('2', '3')";
    $types  = "s";
    $params = [$customer_id];

    if (count($sale_ids) > 0) {
        // ເລືອກບິນເອງ → ໃຊ້ສະເພາະບິນເຫຼົ່ານັ້ນ (ບິນຂອງຄົນອື່ນຈະບໍ່ຖືກນັບ ເພາະກວດ customer_id ແລ້ວ)
        $sql    .= " AND sale_id IN (" . implode(',', array_fill(0, count($sale_ids), '?')) . ")";
        $types  .= str_repeat('s', count($sale_ids));
        foreach ($sale_ids as $sid) $params[] = (string)$sid;
    } else {
        // ບໍ່ໄດ້ເລືອກ → ໃຊ້ຕົວກອງ
        if ($search !== '') {
            $sql     .= " AND sale_id LIKE ?";
            $types   .= "s";
            $params[] = "%" . $search . "%";
        }
        if ($from_date !== '' && $to_date !== '') {
            $sql     .= " AND sale_date BETWEEN ? AND ?";
            $types   .= "ss";
            $params[] = $from_date . " 00:00:00";
            $params[] = $to_date . " 23:59:59";
        }
    }
    $sql .= " GROUP BY sale_id HAVING MAX(remain) > 0";

    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) throw new Exception("prepare failed: " . mysqli_error($con));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $bills = [];
    $total = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $bills[] = (string)$row['sale_id'];
        $total  += (float)$row['remain'];
    }
    mysqli_stmt_close($stmt);

    if (count($bills) === 0 || $total <= 0) {
        out([
            "status"  => "error",
            "code"    => "no_bills",
            "message" => "ບໍ່ມີບິນຄ້າງຈ່າຍໃນລາຍການທີ່ເລືອກ"
        ]);
    }

    sort($bills);
    $amount        = (string)(int)round($total);
    $sale_ids_json = json_encode($bills);

    // ---------- 4) ມີ QR ເກົ່າທີ່ຍັງບໍ່ໝົດອາຍຸ ສຳລັບບິນຊຸດດຽວກັນບໍ? ຖ້າມີໃຊ້ອັນເດີມ (ກັນຈ່າຍຊ້ຳ) ----------
    $chk = mysqli_prepare($con, "SELECT bill_number, qr_data, expires_at FROM payments
                                 WHERE customer_id = ? AND status = 'pending' AND expires_at > ?
                                   AND sale_ids = ? AND amount = ?
                                   AND qr_data IS NOT NULL AND qr_data <> ''
                                 ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($chk, "ssss", $customer_id, $now, $sale_ids_json, $amount);
    mysqli_stmt_execute($chk);
    $old = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
    mysqli_stmt_close($chk);

    if ($old) {
        $left = strtotime($old['expires_at']) - time();
        if ($left > 5) {
            out([
                "status" => "success",
                "data"   => [
                    "bill_number" => $old['bill_number'],
                    "amount"      => (int)$amount,
                    "bill_count"  => count($bills),
                    "qr_data"     => $old['qr_data'],
                    "expires_at"  => $old['expires_at'],
                    "expires_in"  => $left,
                    "sale_ids"    => $bills,
                    "reused"      => true,
                ],
            ]);
        }
    }

    // ---------- 5) ສ້າງລາຍການໃໝ່ ແລະ ບັນທຶກ pending ກ່ອນເອີ້ນທະນາຄານ ----------
    $expire_ts  = time() + QR_EXPIRE_MINUTES * 60;
    $exp_date   = date('YmdHi', $expire_ts);              // yyyymmddHHmm
    $expires_at = date('Y-m-d H:i:s', $expire_ts);

    $inserted    = false;
    $bill_number = '';
    $request_id  = '';
    for ($try = 0; $try < 3 && !$inserted; $try++) {
        // billNumber ຕ້ອງບໍ່ຊ້ຳ ແລະ ສັ້ນ (tag 62 ຕ້ອງບໍ່ເກີນ 99 ຕົວອັກສອນ)
        $bill_number = 'EX' . date('ymdHis') . random_int(10, 99);
        $request_id  = make_guid();

        $ins = mysqli_prepare($con, "INSERT INTO payments (bill_number, request_id, customer_id, sale_ids, amount, status, expires_at)
                                     VALUES (?, ?, ?, ?, ?, 'pending', ?)");
        if (!$ins) throw new Exception("prepare insert failed: " . mysqli_error($con));
        mysqli_stmt_bind_param($ins, "ssssss", $bill_number, $request_id, $customer_id, $sale_ids_json, $amount, $expires_at);

        try {
            $ok    = mysqli_stmt_execute($ins);
            $errno = $ok ? 0 : mysqli_stmt_errno($ins);
        } catch (mysqli_sql_exception $ex2) {
            $ok    = false;
            $errno = (int)$ex2->getCode();
        }
        mysqli_stmt_close($ins);

        if ($ok) {
            $inserted = true;
        } elseif ($errno !== 1062) {            // 1062 = bill_number ຊ້ຳ → ລອງສ້າງໃໝ່
            throw new Exception("insert payments failed errno=$errno");
        }
    }
    if (!$inserted) throw new Exception("cannot create unique bill_number");

    // ---------- 6) ສ້າງຂໍ້ມູນສົ່ງທະນາຄານ ----------
    // verify = MD5( KEY + merchantId + terminalId + merchantName + billNumber ) ຕົວພິມໃຫຍ່
    $verify = strtoupper(md5(BANK_VERIFY_KEY . BANK_MERCHANT_ID . BANK_TERMINAL_ID . BANK_MERCHANT_NAME . $bill_number));

    $body = [
        "requestId"      => $request_id,
        "channelId"      => "WEB",
        "merchantId"     => BANK_MERCHANT_ID,
        "terminalId"     => BANK_TERMINAL_ID,
        "merchantName"   => BANK_MERCHANT_NAME,
        "amount"         => $amount,
        "merchantCity"   => BANK_MERCHANT_CITY,
        "billNumber"     => $bill_number,
        "expDate"        => $exp_date,
        // ຄວບຄຸມຄວາມຍາວ: ໃຊ້ A-Z ແລະ ຕົວເລກເທົ່ານັ້ນ (tag 62 ສູງສຸດ 99)
        "customerNumber" => substr(preg_replace('/[^A-Za-z0-9]/', '', $customer_id), -8),
        "purpose"        => "Invoice",
        "currency"       => "LAK",
        "preseve1"       => "",
        "preseve2"       => "",
        "preseve3"       => "",
    ];

    // ---------- 7) ເອີ້ນ API ທະນາຄານ ----------
    $ch = curl_init(BANK_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "X-Source-Request-Verify: " . $verify,
            "X-Source-Router: genqr",
            "User: " . BANK_USER,
            "Passwd: " . BANK_PASSWD,
        ],
        CURLOPT_POSTFIELDS     => json_encode($body),
    ]);
    $res      = curl_exec($ch);
    $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    // ເກັບຄຳຂໍ ແລະ ຄຳຕອບດິບຂອງທະນາຄານ ລົງ payments ເພື່ອກວດສາເຫດ
    save_bank_debug($con, $bill_number, $http, $res, $curl_err, $body);

    $resp = ($res !== false) ? json_decode($res, true) : null;
    $r    = is_array($resp) ? ($resp['json'] ?? $resp) : [];
    $code = $r['status']['code'] ?? null;
    $msg  = $r['status']['message'] ?? ('http ' . $http . ' ' . ($curl_err ?: substr(preg_replace('/\s+/', ' ', (string)$res), 0, 180)));
    $qr   = $r['qrData'] ?? '';

    if ($code === '00' && $qr !== '') {

        // ກັນ QR ທີ່ tag 62 ຍາວເກີນ 99 (ບາງແອັບທະນາຄານສະແກນບໍ່ໄດ້)
        $len62 = emv_tag_length($qr, '62');
        if ($len62 !== null && $len62 > 99) {
            mark_failed($con, $bill_number, 'TAG62', "tag62 too long ($len62)");
            error_log("create_payment: tag62 too long ($len62) bill=$bill_number");
            out(["status" => "error", "message" => "ບໍ່ສາມາດສ້າງ QR ໄດ້ ກະລຸນາລອງໃໝ່ອີກຄັ້ງ"]);
        }

        // ບັນທຶກ QR ລົງ payments
        $upd = mysqli_prepare($con, "UPDATE payments SET qr_data = ?, bank_code = ?, bank_message = ? WHERE bill_number = ?");
        mysqli_stmt_bind_param($upd, "ssss", $qr, $code, $msg, $bill_number);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);

        out([
            "status" => "success",
            "data"   => [
                "bill_number" => $bill_number,
                "amount"      => (int)$amount,
                "bill_count"  => count($bills),
                "qr_data"     => $qr,
                "expires_at"  => $expires_at,
                "expires_in"  => QR_EXPIRE_MINUTES * 60,
                "sale_ids"    => $bills,
                "reused"      => false,
            ],
        ]);
    }

    // ທະນາຄານປະຕິເສດ ຫຼື ເຊື່ອມຕໍ່ບໍ່ໄດ້
    mark_failed($con, $bill_number, (string)$code, $msg);
    error_log("create_payment failed bill=$bill_number http=$http code=" . (string)$code . " msg=$msg");
    out(["status" => "error", "message" => "ບໍ່ສາມາດສ້າງ QR ໄດ້ ກະລຸນາລອງໃໝ່ອີກຄັ້ງ"]);

} catch (Throwable $e) {
    error_log("create_payment exception: " . $e->getMessage());
    out(["status" => "error", "message" => "ເກີດຂໍ້ຜິດພາດ ກະລຸນາລອງໃໝ່ອີກຄັ້ງ"]);
}