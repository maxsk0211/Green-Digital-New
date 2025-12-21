<?php
require_once '../../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit();
}

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // ลบสมาชิก
    $sql = "DELETE FROM users WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header('Location: ../users.php?success=deleted');
    } else {
        header('Location: ../users.php?error=failed');
    }
    exit();
} else {
    header('Location: ../users.php');
    exit();
}
?>
