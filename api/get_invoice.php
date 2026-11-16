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

    $action = isset($_POST['action']) ? $_POST['action'] : 'list';

    if($action == 'list') {
        $search = isset($_POST['search']) ? mysqli_real_escape_string($con, $_POST['search']) : '';
        $from_date = isset($_POST['from_date']) ? mysqli_real_escape_string($con, $_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? mysqli_real_escape_string($con, $_POST['to_date']) : '';

        $where = " AND product_sale.customer_id LIKE '%$customer_id%' ";

        if($search != '') {
            $where .= " AND product_sale.sale_id LIKE '%$search%' "; 
        }

        if($from_date != '' && $to_date != '') {
            $where .= " AND product_sale.sale_date BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59' ";
        }

        // ບວກທຸກແຖວ: product_sale ເກັບ 1 ແຖວຕໍ່ 1 ລັອດສະຕ໋ອກ (ເບິ່ງ insert_add_sale_stock.php)
        // ສິນຄ້າດຽວກັນທີ່ຕັດມາຈາກຫຼາຍລັອດ ຈະມີຫຼາຍແຖວ ແລະ ທຸກແຖວເປັນຂອງຈິງ
        // ສະນັ້ນຕ້ອງບວກໃຫ້ໝົດ ຈຶ່ງໄດ້ຈຳນວນ ແລະ ມູນຄ່າທີ່ຖືກຕ້ອງ
        $sql = "SELECT product_sale.sale_id, product_sale.sale_date, product_sale.status_payment, product_sale.status, product_sale.order_id,
                sum(product_sale.qty*product_sale.price) as total_amt,
                sum(product_sale.qty) as total_qty,
                remain
                FROM product_sale
                WHERE 1=1 $where
                GROUP BY product_sale.sale_id
                ORDER BY product_sale.sale_date DESC, product_sale.sale_id DESC LIMIT 100";

        $query = mysqli_query($con, $sql);
        if(!$query) {
            throw new Exception("ເກີດຂໍ້ຜິດພາດ: " . mysqli_error($con));
        }
        
        $data = [];
        while($row = mysqli_fetch_assoc($query)) {
            $data[] = $row;
        }
        
        ob_end_clean();
        echo json_encode(["status" => "success", "data" => $data], JSON_INVALID_UTF8_SUBSTITUTE);
        
    } elseif($action == 'details') {
        $sale_id = isset($_POST['sale_id']) ? mysqli_real_escape_string($con, $_POST['sale_id']) : '';
        
        // verify ownership
        $verify_where = " product_sale.customer_id LIKE '%$customer_id%' ";

        $verify_sql = "SELECT sale_id FROM product_sale WHERE sale_id='$sale_id' AND $verify_where";
        $verify_q = mysqli_query($con, $verify_sql);
        if(!$verify_q) {
            throw new Exception("ເກີດຂໍ້ຜິດພາດ: " . mysqli_error($con));
        }
        if(mysqli_num_rows($verify_q) == 0) {
            ob_end_clean();
            echo json_encode(["status" => "error", "message" => "ບໍ່ມີສິດເຂົ້າເຖິງຂໍ້ມູນນີ້"]);
            exit;
        }

        // ບວກລັອດເຂົ້າກັນ ໃຫ້ເຫຼືອ 1 ແຖວຕໍ່ 1 ສິນຄ້າ ຄືກັບ popup ຂອງ admin
        // (exim/admin/fetch_product_sale_detail.php ໃຊ້ sum(qty) group by product_id)
        // ຖ້າບໍ່ບວກ ລູກຄ້າຈະເຫັນສິນຄ້າດຽວກັນຊ້ຳຫຼາຍແຖວ ຕາມຈຳນວນລັອດ
        $sql = "SELECT product_sale.qty as qty, product_sale.price,
                (product_sale.qty * product_sale.price) as amount,
                -- SUM(product_sale.qty * product_sale.price) as amount,
                products.Product_Name, products.Unit
                FROM product_sale
                LEFT JOIN products ON products.Product_ID = product_sale.product_id
                WHERE product_sale.sale_id = '$sale_id' AND $verify_where
                -- GROUP BY product_sale.product_id
                ";
                
        $query = mysqli_query($con, $sql);
        if(!$query) {
            throw new Exception("ເກີດຂໍ້ຜິດພາດ: " . mysqli_error($con));
        }
        
        $data = [];
        while($row = mysqli_fetch_assoc($query)) {
            $data[] = $row;
        }
        
        ob_end_clean();
        echo json_encode(["status" => "success", "data" => $data], JSON_INVALID_UTF8_SUBSTITUTE);
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
