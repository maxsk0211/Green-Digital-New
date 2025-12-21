<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ตรวจสอบว่ามีการส่งข้อมูลมา
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reward_id = intval($_POST['reward_id']);
    $reward_name = mysqli_real_escape_string($conn, $_POST['reward_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $points_required = intval($_POST['points_required']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $category = $_POST['category'];
    $status = $_POST['status'];

    // อัปเดตของรางวัล
    $sql = "UPDATE rewards
            SET reward_name = '$reward_name',
                description = '$description',
                points_required = $points_required,
                stock_quantity = $stock_quantity,
                category = '$category',
                status = '$status'
            WHERE reward_id = $reward_id";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = 'แก้ไขของรางวัลสำเร็จ!';
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
    }
} else {
    $_SESSION['error'] = 'ไม่พบข้อมูลที่ส่งมา';
}

header("Location: ../rewards.php");
exit();
?>
