<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ตรวจสอบว่ามี ID ส่งมา
if (isset($_GET['id'])) {
    $reward_id = intval($_GET['id']);

    // เช็คว่ามีการแลกของรางวัลนี้หรือยัง
    $check_sql = "SELECT COUNT(*) as count FROM reward_redemptions WHERE reward_id = $reward_id";
    $check_result = mysqli_query($conn, $check_sql);
    $check_data = mysqli_fetch_assoc($check_result);

    if ($check_data['count'] > 0) {
        $_SESSION['error'] = 'ไม่สามารถลบได้ เนื่องจากมีการแลกของรางวัลนี้แล้ว';
    } else {
        // ลบของรางวัล
        $sql = "DELETE FROM rewards WHERE reward_id = $reward_id";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = 'ลบของรางวัลสำเร็จ!';
        } else {
            $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
        }
    }
} else {
    $_SESSION['error'] = 'ไม่พบรหัสของรางวัล';
}

header("Location: ../rewards.php");
exit();
?>
