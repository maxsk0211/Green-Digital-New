<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $price_id = $_GET['id'];

    // ลบราคา
    $sql = "DELETE FROM prices WHERE price_id = '$price_id'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../prices.php?success=deleted");
    } else {
        header("Location: ../prices.php?error=failed");
    }
} else {
    header("Location: ../prices.php");
}
?>
