<?php
require_once '../config.php';

// ตรวจสอบว่าเป็นสมาชิก
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // ตรวจสอบรหัสผ่านใหม่ตรงกันหรือไม่
    if ($new_password != $confirm_password) {
        header("Location: ../profile.php?error=password_mismatch");
        exit();
    }

    // ดึงข้อมูลผู้ใช้
    $user_sql = "SELECT password FROM users WHERE user_id = '$user_id'";
    $user_result = mysqli_query($conn, $user_sql);
    $user = mysqli_fetch_assoc($user_result);

    // ตรวจสอบรหัสผ่านเดิม
    if (!password_verify($old_password, $user['password'])) {
        header("Location: ../profile.php?error=wrong_password");
        exit();
    }

    // เข้ารหัสรหัสผ่านใหม่
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // อัปเดตรหัสผ่าน
    $sql = "UPDATE users SET password = '$hashed_password', updated_at = NOW() WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../profile.php?success=password_changed");
    } else {
        header("Location: ../profile.php?error=failed");
    }
} else {
    header("Location: ../profile.php");
}
?>
