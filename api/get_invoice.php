<?php
session_start();
require_once "../config.php";

header('Content-Type: application/json; charset=utf-8');

$customer_id = isset($_SESSION['customer_id']) ? mysqli_real_escape_string($con, $_SESSION['customer_id']) : '';

if(empty($customer_id)) {
    echo json_encode(["status" => "error", "message" => "Not logged in or Session expired"]);
    exit;
}
session_write_close();

$action = isset($_POST['action']) ? $_POST['action'] : 'list';

if($action == 'list') {
    $search = isset($_POST['search']) ? mysqli_real_escape_string($con, $_POST['search']) : '';
    $from_date = isset($_POST['from_date']) ? mysqli_real_escape_string($con, $_POST['from_date']) : '';
    $to_date = isset($_POST['to_date']) ? mysqli_real_escape_string($con, $_POST['to_date']) : '';

    $where = " AND product_sale.customer_id = '$customer_id' ";

    if($search != '') {
        $where .= " AND product_sale.sale_id LIKE '%$search%' "; 
    }

    if($from_date != '' && $to_date != '') {
        $where .= " AND product_sale.sale_date BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59' ";
    }

    $sql = "SELECT product_sale.sale_id, product_sale.sale_date, product_sale.status_payment, product_sale.status, product_sale.order_id,
            sum(product_sale.qty*product_sale.price) as total_amt, 
            sum(product_sale.qty) as total_qty
            FROM product_sale 
            WHERE 1=1 $where 
            GROUP BY product_sale.sale_id 
            ORDER BY product_sale.sale_date DESC, product_sale.sale_id DESC LIMIT 100";

    $query = mysqli_query($con, $sql);
    $data = [];
    if($query) {
        while($row = mysqli_fetch_assoc($query)) {
            $data[] = $row;
        }
    }
    echo json_encode(["status" => "success", "data" => $data]);
    
} elseif($action == 'details') {
    $sale_id = isset($_POST['sale_id']) ? mysqli_real_escape_string($con, $_POST['sale_id']) : '';
    
    $sql = "SELECT product_sale.qty, product_sale.price, (product_sale.qty * product_sale.price) as amount, 
            products.Product_Name, products.Unit 
            FROM product_sale 
            LEFT JOIN products ON products.Product_ID = product_sale.product_id
            WHERE product_sale.sale_id = '$sale_id' AND product_sale.customer_id = '$customer_id'";
            
    $query = mysqli_query($con, $sql);
    $data = [];
    if($query) {
        while($row = mysqli_fetch_assoc($query)) {
            $data[] = $row;
        }
    }
    echo json_encode(["status" => "success", "data" => $data]);
}
?>
