<?php
session_start();
require_once '../config.php';

// เช็คว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // รับค่าจาก Form
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $province = $_POST['province'];
    $district = $_POST['district'];
    $security_answer = $_POST['security_answer'];

    // เช็คค่า NULL
    if (empty($email) || empty($password) || empty($confirm_password) ||
        empty($first_name) || empty($last_name) || empty($phone) ||
        empty($address) || empty($province) || empty($district) || empty($security_answer)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        header("Location: ../user_register.php");
        exit();
    }

    // เช็ครหัสผ่านตรงกัน
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "รหัสผ่านไม่ตรงกัน";
        header("Location: ../user_register.php");
        exit();
    }

    // เช็ครหัสผ่านความยาว
    if (strlen($password) < 6) {
        $_SESSION['error'] = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
        header("Location: ../user_register.php");
        exit();
    }

    // เช็คว่าอีเมลซ้ำหรือไม่
    $check_sql = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error'] = "อีเมลนี้มีในระบบแล้ว";
        header("Location: ../user_register.php");
        exit();
    }

    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // บันทึกข้อมูล
    $sql = "INSERT INTO users (email, password, first_name, last_name, phone, address, province, district, security_answer, user_level, total_points, status)
            VALUES ('$email', '$hashed_password', '$first_name', '$last_name', '$phone', '$address', '$province', '$district', '$security_answer', 'Bronze', 0, 'active')";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        // สำเร็จ - ส่ง Session กลับไป
        $_SESSION['success'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
        header("Location: ../user_login.php");
    } else {
        // ล้มเหลว
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
        header("Location: ../user_register.php");
    }

    // ปิดการเชื่อมต่อ
    mysqli_close($conn);
    exit();

} else {
    header("Location: ../user_register.php");
    exit();
}
