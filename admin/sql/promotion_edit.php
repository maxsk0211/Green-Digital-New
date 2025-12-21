<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ตรวจสอบว่ามีการส่งข้อมูลมา
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $promotion_id = $_POST['promotion_id'];
    $promotion_name = $_POST['promotion_name'];
    $code = $_POST['code'];
    $description = $_POST['description'];
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'];
    $min_purchase = $_POST['min_purchase'];
    $usage_limit = $_POST['usage_limit'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_active = $_POST['is_active'];

    // จัดการ usage_limit
    if ($usage_limit == '') {
        $usage_limit = 'NULL';
    } else {
        $usage_limit = "'$usage_limit'";
    }

    // อัปเดตโปรโมชั่น
    $sql = "UPDATE promotions
            SET promotion_name = '$promotion_name', code = '$code', description = '$description',
                discount_type = '$discount_type', discount_value = '$discount_value', min_purchase = '$min_purchase',
                usage_limit = $usage_limit, start_date = '$start_date', end_date = '$end_date', is_active = '$is_active'
            WHERE promotion_id = '$promotion_id'";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../promotions.php?success=updated");
    } else {
        header("Location: ../promotions.php?error=failed");
    }
} else {
    header("Location: ../promotions.php");
}
?>
