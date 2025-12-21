<?php
session_start();
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);

    // ค้นหาผู้ใช้จากอีเมลหรือเบอร์โทร
    $sql = "SELECT user_id, email, first_name, last_name FROM users
            WHERE email = '$username' OR phone = '$username'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // เก็บ user_id ไว้ใน session
        $_SESSION['forgot_user_id'] = $user['user_id'];
        $_SESSION['forgot_email'] = $user['email'];
        $_SESSION['forgot_name'] = $user['first_name'] . ' ' . $user['last_name'];

        // กลับไปหน้า forgot_password.php เพื่อแสดง Step 2
        header("Location: ../forgot_password.php");
        exit();
    } else {
        // ไม่พบผู้ใช้
        header("Location: ../forgot_password.php?error=not_found");
        exit();
    }
} else {
    header("Location: ../forgot_password.php");
    exit();
}
?>
