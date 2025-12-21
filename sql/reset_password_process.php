<?php
session_start();
require_once '../config.php';

// ตรวจสอบว่า verify แล้วหรือยัง
if (!isset($_SESSION['reset_verified']) || !isset($_SESSION['forgot_user_id'])) {
    header("Location: ../forgot_password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['forgot_user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // ตรวจสอบรหัสผ่านตรงกันหรือไม่
    if ($new_password !== $confirm_password) {
        header("Location: ../reset_password.php?error=password_mismatch");
        exit();
    }

    // ตรวจสอบความยาว
    if (strlen($new_password) < 6) {
        header("Location: ../reset_password.php?error=password_short");
        exit();
    }

    // เข้ารหัสรหัสผ่านใหม่
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // อัปเดตรหัสผ่านในฐานข้อมูล
    $sql = "UPDATE users SET password = '$hashed_password' WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        // สำเร็จ - ลบ session และกลับไปหน้า login
        session_unset();
        session_destroy();

        header("Location: ../user_login.php?success=password_reset");
        exit();
    } else {
        // ล้มเหลว
        header("Location: ../reset_password.php?error=update_failed");
        exit();
    }
} else {
    header("Location: ../reset_password.php");
    exit();
}
?>
