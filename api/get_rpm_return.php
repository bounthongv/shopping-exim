<?php
session_start();
require_once "../config.php";

header('Content-Type: application/json; charset=utf-8');

// Catch any unexpected output (like PHP warnings)
ob_start();

try {
    $customer_id = isset($_SESSION['customer_id']) ? mysqli_real_escape_string($con, $_SESSION['customer_id']) : '';

    if(empty($customer_id)) {
        ob_end_clean();
        echo json_encode(["status" => "error", "message" => "ກະລຸນາເຂົ້າສູ່ລະບົບ"]);
        exit;
    }

    $customer_name = isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : '';
    $outlet_name_la = '';

    $stmt = mysqli_prepare($con, "
        SELECT c.customer_name, i.outlet_name_la 
        FROM customers c 
        LEFT JOIN customer_import i ON c.customer_id = i.external_id 
        WHERE c.customer_id = ?
    ");
    if($stmt){
        mysqli_stmt_bind_param($stmt, "s", $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($shop = mysqli_fetch_assoc($result)){
            $customer_name = !empty($shop['customer_name']) ? $shop['customer_name'] : $customer_name;
            $outlet_name_la = !empty($shop['outlet_name_la']) ? $shop['outlet_name_la'] : '';
        }
        mysqli_stmt_close($stmt);
    }

    $c_name_esc = mysqli_real_escape_string($con, $customer_name);
    $o_name_esc = mysqli_real_escape_string($con, $outlet_name_la);

    session_write_close();

    $action = isset($_POST['action']) ? $_POST['action'] : 'list';

    if($action == 'list') {
        $search = isset($_POST['search']) ? mysqli_real_escape_string($con, $_POST['search']) : '';
        $from_date = isset($_POST['from_date']) ? mysqli_real_escape_string($con, $_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? mysqli_real_escape_string($con, $_POST['to_date']) : '';

        $where = " AND (tbl_emp_no.Distributor_Name LIKE '%$customer_id%' 
                    OR tbl_emp_no.Distributor_Name LIKE '%$c_name_esc%' ";
        if(!empty($o_name_esc)) {
            $where .= " OR tbl_emp_no.Distributor_Name LIKE '%$o_name_esc%' ";
        }
        $where .= ") ";

        if($search != '') {
            $where .= " AND tbl_emp_no.no LIKE '%$search%' "; 
        }

        if($from_date != '' && $to_date != '') {
            $where .= " AND tbl_emp_no.Sending_date BETWEEN '$from_date' AND '$to_date' ";
        }

        $sql = "SELECT tbl_emp_no.no as sale_id, tbl_emp_no.Sending_date as sale_date, 
                tbl_emp_no.Truck_Number, tbl_emp_no.Driver_Name,
                SUM(IF(tbl_empty_return_note.Storekeeper_Count='' OR tbl_empty_return_note.Storekeeper_Count IS NULL, 0, tbl_empty_return_note.Storekeeper_Count)) as total_qty
                FROM tbl_emp_no 
                LEFT JOIN tbl_empty_return_note ON tbl_emp_no.no = tbl_empty_return_note.no
                WHERE 1=1 $where 
                GROUP BY tbl_emp_no.no 
                ORDER BY tbl_emp_no.Sending_date DESC, tbl_emp_no.no DESC LIMIT 100";

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
        $verify_where = " (Distributor_Name LIKE '%$customer_id%' OR Distributor_Name LIKE '%$c_name_esc%' ";
        if(!empty($o_name_esc)) {
            $verify_where .= " OR Distributor_Name LIKE '%$o_name_esc%' ";
        }
        $verify_where .= ") ";

        $verify_sql = "SELECT no FROM tbl_emp_no WHERE no='$sale_id' AND $verify_where";
        $verify_q = mysqli_query($con, $verify_sql);
        if(!$verify_q) {
            throw new Exception("ເກີດຂໍ້ຜິດພາດ: " . mysqli_error($con));
        }
        if(mysqli_num_rows($verify_q) == 0) {
            ob_end_clean();
            echo json_encode(["status" => "error", "message" => "ບໍ່ມີສິດເຂົ້າເຖິງຂໍ້ມູນນີ້"]);
            exit;
        }

        $sql = "SELECT IF(tbl_empty_return_note.Storekeeper_Count='' OR tbl_empty_return_note.Storekeeper_Count IS NULL, 0, tbl_empty_return_note.Storekeeper_Count) as qty, 
                tbl_empty_return_note.Description as Product_Name, 
                tbl_empty_return_note.UOM as Unit 
                FROM tbl_empty_return_note 
                WHERE no = '$sale_id' ORDER BY fomu_id ASC";
                
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
