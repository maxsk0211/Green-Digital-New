<?php
require_once '../../config.php';

// ตรวจสอบว่า login และเป็น แอดมิน
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../login.php');
    exit();
}

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // ตรวจสอบรหัสผ่านตรงกันหรือไม่
    if ($password !== $confirm_password) {
        header('Location: ../admins.php?error=password_mismatch');
        exit();
    }

    // ตรวจสอบว่า Username ซ้ำหรือไม่
    $check_sql = "SELECT admin_id FROM admins WHERE username = '$username'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        header('Location: ../admins.php?error=username_exists');
        exit();
    }

    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // INSERT ข้อมูล Admin ใหม่
    $sql = "INSERT INTO admins (username, password, full_name, email, role, created_at)
            VALUES ('$username', '$hashed_password', '$full_name', '$email', '$role', NOW())";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        header('Location: ../admins.php?success=added');
    } else {
        header('Location: ../admins.php?error=failed');
    }
    exit();
} else {
    header('Location: ../admins.php');
    exit();
}
?>
