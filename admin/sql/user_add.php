<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ตรวจสอบว่ามีการส่งข้อมูลมา
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $province = $_POST['province'];
    $district = $_POST['district'];
    $user_level = $_POST['user_level'];
    $status = $_POST['status'];

    // ตรวจสอบว่ารหัสผ่านตรงกันหรือไม่
    if ($password !== $confirm_password) {
        header("Location: ../users.php?error=password_mismatch");
        exit();
    }

    // ตรวจสอบว่า email ซ้ำหรือไม่
    $check_sql = "SELECT user_id FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        header("Location: ../users.php?error=email_exists");
        exit();
    }

    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // เพิ่มสมาชิกใหม่
    $sql = "INSERT INTO users (email, password, first_name, last_name, phone, address, province, district, user_level, status)
            VALUES ('$email', '$hashed_password', '$first_name', '$last_name', '$phone', '$address', '$province', '$district', '$user_level', '$status')";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../users.php?success=added");
    } else {
        header("Location: ../users.php?error=failed");
    }
} else {
    header("Location: ../users.php");
}
?>
