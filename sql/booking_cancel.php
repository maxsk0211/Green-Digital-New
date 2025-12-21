<?php
require_once '../config.php';

// ตรวจสอบว่าเป็นสมาชิก
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $user_id = $_SESSION['user_id'];
    $booking_id = intval($_POST['booking_id']);

    // ตรวจสอบว่าการจองนี้เป็นของผู้ใช้คนนี้จริง
    $check_sql = "SELECT * FROM bookings WHERE booking_id = '$booking_id' AND user_id = '$user_id'";
    $check_result = mysqli_query($conn, $check_sql);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $booking = mysqli_fetch_assoc($check_result);

        // ตรวจสอบสถานะ (สามารถยกเลิกได้เฉพาะ pending และ confirmed)
        if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed') {
            // อัพเดทสถานะเป็น cancelled
            $update_sql = "UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE booking_id = '$booking_id'";

            if (mysqli_query($conn, $update_sql)) {
                // สร้างการแจ้งเตือน
                $notification_title = 'ยกเลิกการจอง';
                $notification_message = "คุณได้ยกเลิกการจองหมายเลข #" . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
                $notification_sql = "INSERT INTO notifications (user_id, title, message, type, is_read)
                                    VALUES ('$user_id', '$notification_title', '$notification_message', 'booking', FALSE)";
                mysqli_query($conn, $notification_sql);

                $_SESSION['success'] = 'ยกเลิกการจองสำเร็จ';
            } else {
                $_SESSION['error'] = 'ไม่สามารถยกเลิกการจองได้';
            }
        } else {
            $_SESSION['error'] = 'ไม่สามารถยกเลิกการจองนี้ได้ เนื่องจากสถานะไม่อนุญาต';
        }
    } else {
        $_SESSION['error'] = 'ไม่พบการจองนี้';
    }
} else {
    $_SESSION['error'] = 'ข้อมูลไม่ถูกต้อง';
}

header("Location: ../bookings.php");
exit();
?>
