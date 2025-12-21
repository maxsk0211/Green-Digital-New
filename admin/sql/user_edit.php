<?php
require_once '../../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit();
}

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $province = $_POST['province'];
    $district = $_POST['district'];
    $user_level = $_POST['user_level'];
    $points = $_POST['points'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    // UPDATE ข้อมูลสมาชิก
    $sql = "UPDATE users
            SET first_name = '$first_name',
                last_name = '$last_name',
                phone = '$phone',
                address = '$address',
                province = '$province',
                district = '$district',
                user_level = '$user_level',
                points = '$points',
                status = '$status',
                updated_at = NOW()";

    // ถ้ามีการเปลี่ยนรหัสผ่าน
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password = '$hashed_password'";
    }

    $sql .= " WHERE user_id = '$user_id'";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        header('Location: ../users.php?success=updated');
    } else {
        header('Location: ../users.php?error=failed');
    }
    exit();
} else {
    header('Location: ../users.php');
    exit();
}
?>
