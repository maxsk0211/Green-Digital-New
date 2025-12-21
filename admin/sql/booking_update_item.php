<?php
require_once '../../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit();
}

// เช็คว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // รับค่าจาก Form
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;

    // เช็คค่า NULL
    if (empty($booking_id) || empty($item_id) || empty($quantity)) {
        $_SESSION['error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        header("Location: ../booking_detail.php?id=$booking_id");
        exit();
    }

    // เช็คว่า quantity มากกว่า 0
    if ($quantity <= 0) {
        $_SESSION['error'] = 'น้ำหนักต้องมากกว่า 0';
        header("Location: ../booking_detail.php?id=$booking_id");
        exit();
    }

    // ดึง price_per_kg จาก booking_items
    $check_sql = "SELECT price_per_kg FROM booking_items WHERE item_id = $item_id AND booking_id = $booking_id";
    $result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($result) == 0) {
        $_SESSION['error'] = 'ไม่พบรายการนี้';
        header("Location: ../booking_detail.php?id=$booking_id");
        exit();
    }

    $item_data = mysqli_fetch_assoc($result);
    $price_per_kg = $item_data['price_per_kg'];

    // คำนวณ subtotal
    $subtotal = $quantity * $price_per_kg;

    // อัพเดทข้อมูลในตาราง booking_items
    $update_sql = "UPDATE booking_items SET quantity = $quantity, subtotal = $subtotal WHERE item_id = $item_id";
    $result = mysqli_query($conn, $update_sql);

    if ($result) {
        // อัพเดท transactions ถ้ามี
        $trans_check = "SELECT transaction_id FROM transactions WHERE booking_id = $booking_id LIMIT 1";
        $trans_result = mysqli_query($conn, $trans_check);

        if (mysqli_num_rows($trans_result) > 0) {
            // คำนวณยอดรวมใหม่
            $total_sql = "SELECT SUM(subtotal) as total_amount FROM booking_items WHERE booking_id = $booking_id";
            $total_result = mysqli_query($conn, $total_sql);
            $total_data = mysqli_fetch_assoc($total_result);
            $new_total = $total_data['total_amount'];

            // อัพเดท transaction
            $update_trans = "UPDATE transactions SET total_amount = $new_total WHERE booking_id = $booking_id";
            mysqli_query($conn, $update_trans);
        }

        $_SESSION['success'] = 'แก้ไขรายการสำเร็จ';
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
    }

    // Redirect กลับหน้าเดิม
    header("Location: ../booking_detail.php?id=$booking_id");
    exit();
}

// ถ้าไม่ได้ส่งแบบ POST ให้กลับหน้าแรก
header("Location: ../bookings.php");
exit();
?>
