<?php
require_once '../../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_SESSION['admin_id'];
    $booking_id = intval($_POST['booking_id']);
    $assigned_to = isset($_POST['assigned_to']) && $_POST['assigned_to'] != '' ? intval($_POST['assigned_to']) : null;

    // ตรวจสอบว่าการจองมีอยู่จริง
    $check_sql = "SELECT * FROM bookings WHERE booking_id = $booking_id";
    $result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($result) == 0) {
        $_SESSION['error'] = 'ไม่พบการจองนี้';
        header("Location: ../bookings.php");
        exit();
    }

    $booking = mysqli_fetch_assoc($result);

    // ตรวจสอบว่า admin ที่จะมอบหมายมีอยู่จริง (ถ้าระบุ)
    if ($assigned_to !== null) {
        $admin_check_sql = "SELECT * FROM admins WHERE admin_id = $assigned_to";
        $admin_result = mysqli_query($conn, $admin_check_sql);

        if (mysqli_num_rows($admin_result) == 0) {
            $_SESSION['error'] = 'ไม่พบผู้ดูแลระบบนี้';
            header("Location: ../booking_detail.php?id=$booking_id");
            exit();
        }
    }

    // อัพเดทการมอบหมาย
    if ($assigned_to !== null) {
        $update_sql = "UPDATE bookings SET assigned_to = $assigned_to, updated_at = NOW() WHERE booking_id = $booking_id";
    } else {
        $update_sql = "UPDATE bookings SET assigned_to = NULL, updated_at = NOW() WHERE booking_id = $booking_id";
    }

    $result = mysqli_query($conn, $update_sql);

    if ($result) {
        $_SESSION['success'] = 'มอบหมายงานสำเร็จ';
    } else {
        $_SESSION['error'] = 'ไม่สามารถมอบหมายงานได้';
    }

    header("Location: ../booking_detail.php?id=$booking_id");
    exit();
} else {
    header("Location: ../bookings.php");
    exit();
}
?>
