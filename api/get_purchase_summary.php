<?php
session_start();
require_once "../config.php";

header('Content-Type: application/json; charset=utf-8');

ob_start();

try {
    $customer_id = isset($_SESSION['customer_id']) ? mysqli_real_escape_string($con, $_SESSION['customer_id']) : '';

    if(empty($customer_id)) {
        ob_end_clean();
        echo json_encode(["status" => "error", "message" => "ກະລຸນາເຂົ້າສູ່ລະບົບ"]);
        exit;
    }

    session_write_close();

    $base_where = " product_sale.customer_id LIKE '%$customer_id%' ";

    $from_date = isset($_POST['from_date']) ? mysqli_real_escape_string($con, $_POST['from_date']) : '';
    $to_date = isset($_POST['to_date']) ? mysqli_real_escape_string($con, $_POST['to_date']) : '';

    $date_where = "";
    if($from_date != '' && $to_date != '') {
        $date_where = " AND product_sale.sale_date BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59' ";
    }

    // ຍອດລວມສະເພາະຊ່ວງເວລາທີ່ເລືອກ
    $sql = "SELECT
                COUNT(DISTINCT product_sale.sale_id) as invoice_count,
                SUM(product_sale.qty) as total_qty,
                SUM(product_sale.qty * product_sale.price) as total_amt
            FROM product_sale
            WHERE $base_where $date_where";

    $query = mysqli_query($con, $sql);
    if(!$query) {
        throw new Exception("ເກີດຂໍ້ຜິດພາດ: " . mysqli_error($con));
    }
    $summary = mysqli_fetch_assoc($query);

    // ວັນທີ່ຊື້ຄັ້ງທຳອິດ ແລະ ຍອດຄ້າງຈ່າຍ: ຂໍ້ມູນຕະຫລອດອາຍຸບັນຊີ ບໍ່ຂຶ້ນກັບຕົວກອງວັນທີ່ທີ່ເລືອກ
    // (ໜີ້ຄ້າງແມ່ນຍອດສະສົມ ບໍ່ແມ່ນຍອດສະເພາະຊ່ວງເວລາ)
    $lifetime_sql = "SELECT
                MIN(product_sale.sale_date) as first_purchase_date,
                SUM(CASE WHEN product_sale.status_payment IS NULL OR product_sale.status_payment = '' OR product_sale.status_payment = '1'
                     THEN product_sale.qty * product_sale.price ELSE 0 END) as debt_amt
            FROM product_sale
            WHERE $base_where";
    $lifetime_query = mysqli_query($con, $lifetime_sql);
    if(!$lifetime_query) {
        throw new Exception("ເກີດຂໍ້ຜິດພາດ: " . mysqli_error($con));
    }
    $lifetime_row = mysqli_fetch_assoc($lifetime_query);

    // ສິນຄ້າທີ່ຊື້ຫຼາຍທີ່ສຸດ (Top 5) ສະເພາະຊ່ວງເວລາທີ່ເລືອກ
    $top_sql = "SELECT products.Product_Name, products.Unit,
                    SUM(product_sale.qty) as qty,
                    SUM(product_sale.qty * product_sale.price) as amt,
                    MAX(product_sale.sale_date) as last_purchase_date
                FROM product_sale
                LEFT JOIN products ON products.Product_ID = product_sale.product_id
                WHERE $base_where $date_where
                GROUP BY product_sale.product_id
                ORDER BY amt DESC
                LIMIT 5";

    $top_query = mysqli_query($con, $top_sql);
    if(!$top_query) {
        throw new Exception("ເກີດຂໍ້ຜິດພາດ: " . mysqli_error($con));
    }
    $top_products = [];
    while($row = mysqli_fetch_assoc($top_query)) {
        $top_products[] = $row;
    }

    $invoice_count = (int)($summary['invoice_count'] ?? 0);
    $total_amt = (float)($summary['total_amt'] ?? 0);

    ob_end_clean();
    echo json_encode([
        "status" => "success",
        "data" => [
            "invoice_count" => $invoice_count,
            "total_qty" => (float)($summary['total_qty'] ?? 0),
            "total_amt" => $total_amt,
            "debt_amt" => (float)($lifetime_row['debt_amt'] ?? 0),
            "avg_per_invoice" => $invoice_count > 0 ? $total_amt / $invoice_count : 0,
            "first_purchase_date" => $lifetime_row['first_purchase_date'] ?? null,
            "top_products" => $top_products
        ]
    ], JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
