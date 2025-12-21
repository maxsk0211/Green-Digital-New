<?php
session_start();
require_once '../config.php';

// ตรวจสอบว่ามี session จาก step 1 หรือไม่
if (!isset($_SESSION['forgot_user_id'])) {
    header("Location: ../forgot_password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['forgot_user_id'];
    $security_answer = $_POST['security_answer']; // ไม่ escape เพราะต้อง case-sensitive

    // ดึงคำตอบที่ถูกต้องจากฐานข้อมูล
    $sql = "SELECT security_answer FROM users WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $correct_answer = $user['security_answer'];

        // เปรียบเทียบคำตอบ (case-sensitive)
        if ($security_answer === $correct_answer) {
            // ถูกต้อง - ให้ไปหน้ารีเซ็ตรหัสผ่าน
            $_SESSION['reset_verified'] = true;
            header("Location: ../reset_password.php");
            exit();
        } else {
            // ผิด - กลับไปหน้า forgot_password พร้อม error
            header("Location: ../forgot_password.php?error=wrong_answer");
            exit();
        }
    } else {
        // ไม่พบข้อมูล
        session_destroy();
        header("Location: ../forgot_password.php?error=not_found");
        exit();
    }
} else {
    header("Location: ../forgot_password.php");
    exit();
}
?>
