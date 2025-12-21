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

    // ลบโปรโมชั่น
    $sql = "DELETE FROM promotions WHERE promotion_id = '$promotion_id'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../promotions.php?success=deleted");
    } else {
        header("Location: ../promotions.php?error=failed");
    }
} else {
    header("Location: ../promotions.php");
}
?>
