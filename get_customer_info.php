<?php
require_once "config.php";
header("Content-Type: application/json; charset=utf-8");

$customer_id = isset($_GET['customer_id']) ? mysqli_real_escape_string($con, trim($_GET['customer_id'])) : "";

$response = ["found" => false];

if ($customer_id !== "") {
    $sql = "
        SELECT c.customer_name, i.outlet_name_la 
        FROM customers c 
        LEFT JOIN customer_import i ON c.customer_id = i.external_id 
        WHERE c.customer_id = '$customer_id' 
        LIMIT 1
    ";
    
    $result = mysqli_query($con, $sql);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $display_name = !empty($row['outlet_name_la']) ? $row['outlet_name_la'] : (!empty($row['customer_name']) ? $row['customer_name'] : '-');
        $response = [
            "found" => true,
            "customer_name" => $display_name
        ];
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
