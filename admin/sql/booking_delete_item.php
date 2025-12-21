<?php
require_once '../../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit();
}

// รับค่า booking_id และ item_id จาก GET
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

// เช็คค่า NULL
if (empty($booking_id) || empty($item_id)) {
    $_SESSION['error'] = 'ข้อมูลไม่ครบถ้วน';
    if ($booking_id > 0) {
        header("Location: ../booking_detail.php?id=$booking_id");
    } else {
        header("Location: ../bookings.php");
    }
    exit();
}

// เช็คว่ามีรายการนี้อยู่จริง
$check_sql = "SELECT item_id FROM booking_items WHERE item_id = $item_id AND booking_id = $booking_id";
$result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = 'ไม่พบรายการนี้';
    header("Location: ../booking_detail.php?id=$booking_id");
    exit();
}

// ลบข้อมูลจากตาราง booking_items
$delete_sql = "DELETE FROM booking_items WHERE item_id = $item_id";
$result = mysqli_query($conn, $delete_sql);

if ($result) {
    // อัพเดท transactions ถ้ามี
    $trans_check = "SELECT transaction_id FROM transactions WHERE booking_id = $booking_id LIMIT 1";
    $trans_result = mysqli_query($conn, $trans_check);

    if (mysqli_num_rows($trans_result) > 0) {
        // คำนวณยอดรวมใหม่
        $total_sql = "SELECT SUM(subtotal) as total_amount FROM booking_items WHERE booking_id = $booking_id";
        $total_result = mysqli_query($conn, $total_sql);
        $total_data = mysqli_fetch_assoc($total_result);
        $new_total = $total_data['total_amount'] ? $total_data['total_amount'] : 0;

        // อัพเดท transaction
        $update_trans = "UPDATE transactions SET total_amount = $new_total WHERE booking_id = $booking_id";
        mysqli_query($conn, $update_trans);
    }

    $_SESSION['success'] = 'ลบรายการสำเร็จ';
} else {
    $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
}

// Redirect กลับหน้าเดิม
header("Location: ../booking_detail.php?id=$booking_id");
exit();
?>
