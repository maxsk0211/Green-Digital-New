<?php
require_once '../config.php';

// ตรวจสอบว่าเป็นสมาชิก
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $province = $_POST['province'];
    $district = $_POST['district'];

    // อัปเดตข้อมูล
    $sql = "UPDATE users
            SET first_name = '$first_name',
                last_name = '$last_name',
                phone = '$phone',
                address = '$address',
                province = '$province',
                district = '$district',
                updated_at = NOW()
            WHERE user_id = '$user_id'";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        // อัปเดต session
        $_SESSION['full_name'] = $first_name . ' ' . $last_name;
        header("Location: ../profile.php?success=updated");
    } else {
        header("Location: ../profile.php?error=failed");
    }
} else {
    header("Location: ../profile.php");
}
?>
