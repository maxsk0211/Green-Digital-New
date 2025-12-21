<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ตรวจสอบว่ามีการส่งข้อมูลมา
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $price_id = $_POST['price_id'];
    $type_id = $_POST['type_id'];
    $price_per_kg = $_POST['price_per_kg'];
    $effective_date = $_POST['effective_date'];
    $is_current = $_POST['is_current'];

    // อัปเดตราคา
    $sql = "UPDATE prices
            SET type_id = '$type_id', price_per_kg = '$price_per_kg', effective_date = '$effective_date', is_current = '$is_current'
            WHERE price_id = '$price_id'";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../prices.php?success=updated");
    } else {
        header("Location: ../prices.php?error=failed");
    }
} else {
    header("Location: ../prices.php");
}
?>
