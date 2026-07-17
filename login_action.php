<?php
session_start();
//require_once "config.php";
include("config.php");


$customer_id = isset($_POST['customer_id']) ? trim($_POST['customer_id']) : "";

if ($customer_id === "") {
    $_SESSION['login_error'] = "ກະລຸນາປ້ອນລະຫັດລູກຄ້າ";
    header("Location: index.php");
    exit;
}


 "SELECT customer_id FROM customers WHERE customer_id ='$customer_id'";

$sql = mysqli_query($con,"SELECT customer_id,customer_name FROM customers WHERE customer_id ='$customer_id' ");
$user=mysqli_fetch_array($sql);


if ($user['customer_id']) {
    //session_regenerate_id(true);
    $_SESSION['customer_id']   = $user['customer_id'];
    $_SESSION['customer_name'] = $user['customer_name'];
    header("Location: menu.php");
    exit;
} else {
    $_SESSION['login_error'] = "ບໍ່ພົບລະຫັດລູກຄ້ານີ້ໃນລະບົບ";
    header("Location: index.php");
    exit;
}

?>