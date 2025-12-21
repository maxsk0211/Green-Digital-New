<?php
require_once '../../config.php';

// ตรวจสอบว่า login แล้วหรือยัง และเป็น Super Admin
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../login.php');
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $admin_id = intval($_GET['id']);
    $action = $_GET['action'];

    // ตรวจสอบว่าไม่ใช่การระงับตัวเอง
    if ($admin_id == $_SESSION['admin_id']) {
        header('Location: ../admins.php?error=suspend_self');
        exit();
    }

    // กำหนดสถานะ
    if ($action == 'suspend') {
        $new_status = 'suspended';
        $success_msg = 'suspended';
    } else {
        $new_status = 'active';
        $success_msg = 'unsuspended';
    }

    // อัปเดตสถานะ
    $sql = "UPDATE admins SET status = '$new_status' WHERE admin_id = $admin_id";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../admins.php?success=$success_msg");
    } else {
        header('Location: ../admins.php?error=failed');
    }
    exit();
} else {
    header('Location: ../admins.php');
    exit();
}
?>
