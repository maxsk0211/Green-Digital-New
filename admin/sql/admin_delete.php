<?php
require_once '../../config.php';

// ตรวจสอบว่า login และเป็น แอดมิน
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../login.php');
    exit();
}

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (isset($_GET['id'])) {
    $admin_id = $_GET['id'];

    // ป้องกันการลบตัวเอง
    if ($admin_id == $_SESSION['admin_id']) {
        header('Location: ../admins.php?error=delete_self');
        exit();
    }

    // ลบ Admin
    $sql = "DELETE FROM admins WHERE admin_id = '$admin_id'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header('Location: ../admins.php?success=deleted');
    } else {
        header('Location: ../admins.php?error=failed');
    }
    exit();
} else {
    header('Location: ../admins.php');
    exit();
}
?>
