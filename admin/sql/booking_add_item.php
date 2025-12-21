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
    $type_id = isset($_POST['type_id']) ? intval($_POST['type_id']) : 0;
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;

    // เช็คค่า NULL
    if (empty($booking_id) || empty($type_id) || empty($quantity)) {
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

    // ดึงข้อมูล price_per_kg จาก prices และ co2_reduction จาก recycle_types
    $type_sql = "SELECT p.price_per_kg, rt.co2_reduction
                 FROM recycle_types rt
                 LEFT JOIN prices p ON rt.type_id = p.type_id AND p.is_current = TRUE
                 WHERE rt.type_id = $type_id";
    $type_result = mysqli_query($conn, $type_sql);

    if (mysqli_num_rows($type_result) == 0) {
        $_SESSION['error'] = 'ไม่พบประเภทขยะนี้';
        header("Location: ../booking_detail.php?id=$booking_id");
        exit();
    }

    $type_data = mysqli_fetch_assoc($type_result);
    $price_per_kg = $type_data['price_per_kg'];

    // เช็คว่ามีราคาหรือไม่
    if (empty($price_per_kg) || $price_per_kg <= 0) {
        $_SESSION['error'] = 'ไม่พบราคารับซื้อสำหรับประเภทขยะนี้';
        header("Location: ../booking_detail.php?id=$booking_id");
        exit();
    }

    // คำนวณ subtotal
    $subtotal = $quantity * $price_per_kg;

    // เพิ่มข้อมูลลงในตาราง booking_items
    $insert_sql = "INSERT INTO booking_items (booking_id, type_id, quantity, price_per_kg, subtotal)
                   VALUES ($booking_id, $type_id, $quantity, $price_per_kg, $subtotal)";
    $result = mysqli_query($conn, $insert_sql);

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

        $_SESSION['success'] = 'เพิ่มรายการสำเร็จ';
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
