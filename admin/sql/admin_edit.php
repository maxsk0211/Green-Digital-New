<?php
require_once '../../config.php';

// ตรวจสอบว่า login และเป็น แอดมิน
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../login.php');
    exit();
}

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id = $_POST['admin_id'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // ถ้ามีการกรอกรหัสผ่านใหม่
    if (!empty($password)) {
        // ตรวจสอบว่ารหัสผ่านตรงกันหรือไม่
        if ($password !== $confirm_password) {
            header('Location: ../admins.php?error=password_mismatch');
            exit();
        }

        // เข้ารหัสรหัสผ่านใหม่
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // UPDATE พร้อมรหัสผ่านใหม่
        $sql = "UPDATE admins SET full_name = '$full_name', email = '$email', role = '$role', password = '$hashed_password' WHERE admin_id = '$admin_id'";
    } else {
        // UPDATE โดยไม่เปลี่ยนรหัสผ่าน
        $sql = "UPDATE admins SET full_name = '$full_name', email = '$email', role = '$role' WHERE admin_id = '$admin_id'";
    }

    $result = mysqli_query($conn, $sql);

    if ($result) {
        header('Location: ../admins.php?success=updated');
    } else {
        header('Location: ../admins.php?error=failed');
    }
    exit();
} else {
    header('Location: ../admins.php');
    exit();
}
?>
