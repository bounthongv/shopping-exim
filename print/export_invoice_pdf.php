<?php 
session_start();
require_once "../config.php";

$customer_id = isset($_SESSION['customer_id']) ? mysqli_real_escape_string($con, $_SESSION['customer_id']) : '';

if(empty($customer_id)) {
    echo "Not logged in or Session expired";
    exit;
}

$from_date = isset($_GET['from_date']) ? mysqli_real_escape_string($con, $_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? mysqli_real_escape_string($con, $_GET['to_date']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';

$where = " AND product_sale.customer_id = '$customer_id' ";

if($search != '') {
    $where .= " AND product_sale.sale_id LIKE '%$search%' "; 
}

if($from_date != '' && $to_date != '') {
    $where .= " AND DATE(product_sale.sale_date) BETWEEN '$from_date' AND '$to_date' ";
}

$sql = "SELECT product_sale.sale_id, product_sale.sale_date, product_sale.status_payment, product_sale.status,
        product_sale.qty, product_sale.price, (product_sale.qty * product_sale.price) as amount,
        products.Product_Name, products.Unit 
        FROM product_sale 
        LEFT JOIN products ON products.Product_ID = product_sale.product_id
        WHERE 1=1 $where 
        ORDER BY product_sale.sale_date DESC, product_sale.sale_id DESC";

$sp = mysqli_query($con, $sql);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="utf-8">
<title>Print Invoice Details</title>
<style type="text/css">
    @import url("https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap");
    @import url("../../exim/admin/LAOS/stylesheet.css");
    body,td,th,h1,h2,h3,h4,h5,h6,p {
        font-family: 'Noto Sans Lao', LAOS, 'Phetsarath OT', Tahoma, sans-serif !important;
    }
    table {
        border-collapse: collapse;
        margin-top: 20px;
    }
    td {
        padding: 8px;
        font-size: 11px;
    }
    th {
        background-color: #E0E0E0;
        text-align: center;
        padding: 8px;
        font-size: 11px;
    }
</style>
</head>
<body onLoad="window.print()">

<?php  
$sql_office = mysqli_query($con," select * from office order by Id desc limit 1");  
$r = mysqli_fetch_array($sql_office);

$customer_query = mysqli_query($con, "SELECT customer_name FROM customers WHERE customer_id = '$customer_id'");
$customer_data = mysqli_fetch_assoc($customer_query);
$customer_name = $customer_data ? $customer_data['customer_name'] : '';
?>  

<table width="900px" align="center" style="border:none;">
    <tr>
    <td width="20%" style="border:none;">
        <img src="../Exim Logo Cars - Copy_1597743348.png" class="img-rounded" alt="Logo" width="80" height="50"> 
        <p><?php echo $r ? $r['office_name'] : ''; ?></p>
    </td>
    <td width="60%" align="center" style="border:none;">
        <h3>ລາຍງານອິນວອຍແບບລະອຽດ / Detailed Invoice Report</h3>
        <h4>ລູກຄ້າ: <?php echo $customer_id . " - " . $customer_name; ?></h4>
        <p>ປະຈຳວັນທີ: &nbsp;<?php echo ($from_date ? date("d/m/Y", strtotime($from_date)) : '-'); ?> &nbsp; ຫາ &nbsp; <?php echo ($to_date ? date("d/m/Y", strtotime($to_date)) : '-'); ?></p>
    </td>
    <td width="20%" align="center" style="border:none;"></td>
    </tr>
</table>

<table border="1" align="center" class="table-bordered" width="900px">
    <tr>
        <th align="center" width="5%">ລ/ດ</th>
        <th align="center" width="12%">ເລກທີບິນ</th>
        <th align="center" width="12%">ວັນທີ</th>
        <th align="center" width="25%">ລາຍການສິນຄ້າ (Description)</th>
        <th align="center" width="8%">ຫົວໜ່ວຍ</th>
        <th align="center" width="10%">ຈຳນວນ</th>
        <th align="center" width="13%">ລາຄາ</th>
        <th align="center" width="15%">ລວມເງິນ</th>
    </tr>
    <?php
    $t_qty = 0;
    $t_amt = 0;
    $i = 1;
    if($sp && mysqli_num_rows($sp) > 0){
        while($s = mysqli_fetch_array($sp)){
            echo "<tr>
                <td align='center'>{$i}</td>
                <td align='center'>{$s['sale_id']}</td>
                <td align='center'>" . date("d-m-Y", strtotime($s['sale_date'])) . "</td>
                <td>{$s['Product_Name']}</td>
                <td align='center'>{$s['Unit']}</td>
                <td align='right'>" . number_format($s['qty'], 2) . "</td>
                <td align='right'>" . number_format($s['price'], 2) . "</td>
                <td align='right'>" . number_format($s['amount'], 2) . "</td>
            </tr>";
            
            $t_qty += $s['qty'];
            $t_amt += $s['amount'];
            $i++;
        }
        echo "<tr style='background-color: #f1f5f9;'>
            <td colspan='5' align='right'><b>ຍອດລວມທັງໝົດ (Grand Total)</b></td>
            <td align='right'><b>" . number_format($t_qty, 2) . "</b></td>
            <td align='right'></td>
            <td align='right'><b>" . number_format($t_amt, 2) . " ₭</b></td>
        </tr>";
    } else {
        echo "<tr><td colspan='8' align='center'>ບໍ່ພົບຂໍ້ມູນ / No Data Found</td></tr>";
    }
    ?>
</table>

</body>
</html>
