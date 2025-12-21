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
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : '';
    $total_weight = isset($_POST['total_weight']) ? floatval($_POST['total_weight']) : 0;
    $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    // เช็คค่า NULL
    if (empty($booking_id) || empty($payment_method) || empty($payment_status)) {
        $_SESSION['error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        header("Location: ../booking_payment.php?id=$booking_id");
        exit();
    }

    // Validate payment_method
    $allowed_methods = ['cash', 'bank_transfer', 'promptpay'];
    if (!in_array($payment_method, $allowed_methods)) {
        $_SESSION['error'] = 'วิธีการชำระเงินไม่ถูกต้อง';
        header("Location: ../booking_payment.php?id=$booking_id");
        exit();
    }

    // Validate payment_status
    $allowed_statuses = ['paid', 'pending', 'failed'];
    if (!in_array($payment_status, $allowed_statuses)) {
        $_SESSION['error'] = 'สถานะการชำระเงินไม่ถูกต้อง';
        header("Location: ../booking_payment.php?id=$booking_id");
        exit();
    }

    // ดึงข้อมูล booking
    $booking_sql = "SELECT * FROM bookings WHERE booking_id = $booking_id";
    $booking_result = mysqli_query($conn, $booking_sql);

    if (mysqli_num_rows($booking_result) == 0) {
        $_SESSION['error'] = 'ไม่พบการจองนี้';
        header("Location: ../bookings.php");
        exit();
    }

    $booking = mysqli_fetch_assoc($booking_result);

    // เช็คว่าสถานะเป็น confirmed หรือไม่
    if ($booking['status'] != 'confirmed') {
        $_SESSION['error'] = 'ไม่สามารถชำระเงินได้ เนื่องจากสถานะไม่ใช่ "ยืนยันแล้ว"';
        header("Location: ../booking_detail.php?id=$booking_id");
        exit();
    }

    // เช็คว่ามี transaction อยู่แล้วหรือไม่
    $check_trans = "SELECT transaction_id FROM transactions WHERE booking_id = $booking_id LIMIT 1";
    $trans_result = mysqli_query($conn, $check_trans);

    if (mysqli_num_rows($trans_result) > 0) {
        $_SESSION['error'] = 'มี transaction สำหรับการจองนี้อยู่แล้ว';
        header("Location: ../booking_detail.php?id=$booking_id");
        exit();
    }

    // เริ่ม Transaction
    mysqli_begin_transaction($conn);

    try {
        // 1. อัพเดทสถานะเป็น completed
        $update_booking = "UPDATE bookings SET status = 'completed', updated_at = NOW() WHERE booking_id = $booking_id";
        mysqli_query($conn, $update_booking);

        // 2. สร้าง transaction
        $user_id = $booking['user_id'];
        $insert_trans = "INSERT INTO transactions (booking_id, user_id, total_weight, total_amount, payment_method, payment_status, payment_date, created_at)
                         VALUES ($booking_id, $user_id, $total_weight, $total_amount, '$payment_method', '$payment_status', NOW(), NOW())";
        mysqli_query($conn, $insert_trans);

        // 3. ระบบแต้มสะสม (Points System)
        // คำนวณแต้ม: 100 บาท = 1 แต้ม
        if ($payment_status == 'paid' && $total_amount > 0) {
            $points_earned = floor($total_amount / 100); // ปัดลง

            if ($points_earned > 0) {
                // เช็คว่าเคยให้แต้มสำหรับ booking_id นี้แล้วหรือยัง (ป้องกันการให้ซ้ำ)
                $check_points = "SELECT transaction_id FROM point_transactions WHERE booking_id = $booking_id LIMIT 1";
                $points_result = mysqli_query($conn, $check_points);

                if (mysqli_num_rows($points_result) == 0) {
                    // อัพเดทแต้มในตาราง users
                    $update_points = "UPDATE users SET points = points + $points_earned WHERE user_id = $user_id";
                    mysqli_query($conn, $update_points);

                    // บันทึกประวัติการได้แต้มในตาราง point_transactions
                    $points_description = "ได้รับแต้มจากการจองหมายเลข #" . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
                    $insert_points = "INSERT INTO point_transactions (user_id, booking_id, points, amount, transaction_type, description, created_at)
                                     VALUES ($user_id, $booking_id, $points_earned, $total_amount, 'earn', '$points_description', NOW())";
                    mysqli_query($conn, $insert_points);
                }
            }
        }

        // 3.5 บันทึก Carbon Footprint
        if ($payment_status == 'paid') {
            // คำนวณ CO2 ที่ลดได้จาก booking_items
            $co2_sql = "SELECT SUM(bi.quantity * rt.co2_reduction) as total_co2
                        FROM booking_items bi
                        JOIN recycle_types rt ON bi.type_id = rt.type_id
                        WHERE bi.booking_id = $booking_id";
            $co2_result = mysqli_query($conn, $co2_sql);
            $co2_data = mysqli_fetch_assoc($co2_result);
            $co2_reduced = $co2_data['total_co2'] ?? 0;

            if ($co2_reduced > 0) {
                // เช็คว่าเคยบันทึก CO2 สำหรับ booking_id นี้แล้วหรือยัง (ป้องกันการบันทึกซ้ำ)
                $check_carbon = "SELECT footprint_id FROM carbon_footprint WHERE booking_id = $booking_id LIMIT 1";
                $carbon_result = mysqli_query($conn, $check_carbon);

                if (mysqli_num_rows($carbon_result) == 0) {
                    // คำนวณค่าอื่นๆ จาก CO2
                    // 1 ต้นไม้ดูดซับ CO2 ได้ประมาณ 21 kg/ปี
                    $trees_equivalent = $co2_reduced / 21;
                    // ประมาณการพลังงานที่ประหยัดได้ (1 kg CO2 ~ 0.5 kWh)
                    $energy_saved = $co2_reduced * 0.5;

                    // บันทึกลงตาราง carbon_footprint
                    $insert_carbon = "INSERT INTO carbon_footprint (user_id, booking_id, co2_reduced, trees_equivalent, energy_saved, created_at)
                                     VALUES ($user_id, $booking_id, $co2_reduced, $trees_equivalent, $energy_saved, NOW())";
                    mysqli_query($conn, $insert_carbon);
                }
            }
        }

        // 4. สร้างการแจ้งเตือนให้ผู้ใช้
        $notification_title = 'การจองของคุณเสร็จสิ้นแล้ว';
        $notification_message = "การจองหมายเลข #" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) .
                               " เสร็จสิ้นแล้ว ยอดชำระเงิน: " . number_format($total_amount, 2) . " บาท";

        // เพิ่มข้อความแต้มในการแจ้งเตือน (ถ้ามี)
        if ($payment_status == 'paid' && isset($points_earned) && $points_earned > 0) {
            $notification_message .= " | คุณได้รับ " . $points_earned . " แต้ม";
        }

        // เพิ่มข้อความ CO2 ที่ลดได้ในการแจ้งเตือน (ถ้ามี)
        if ($payment_status == 'paid' && isset($co2_reduced) && $co2_reduced > 0) {
            $notification_message .= " | ช่วยลด CO2 ได้ " . number_format($co2_reduced, 2) . " kg";
        }

        $notification_sql = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                            VALUES ($user_id, '$notification_title', '$notification_message', 'booking', 0, NOW())";
        mysqli_query($conn, $notification_sql);

        // Commit Transaction
        mysqli_commit($conn);

        $_SESSION['success'] = 'ชำระเงินสำเร็จ! การจองเสร็จสิ้นแล้ว';

    } catch (Exception $e) {
        // Rollback ถ้ามีข้อผิดพลาด
        mysqli_rollback($conn);
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }

    // Redirect กลับหน้า booking_detail
    header("Location: ../booking_detail.php?id=$booking_id");
    exit();
}

// ถ้าไม่ได้ส่งแบบ POST ให้กลับหน้าแรก
header("Location: ../bookings.php");
exit();
?>
