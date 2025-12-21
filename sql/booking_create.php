<?php
require_once '../config.php';

// ตรวจสอบว่าเป็นสมาชิก
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $booking_time = mysqli_real_escape_string($conn, $_POST['booking_time']);
    $pickup_address = mysqli_real_escape_string($conn, $_POST['pickup_address']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $selected_types = $_POST['selected_types'] ?? [];
    $weights = $_POST['weights'] ?? [];

    // ตรวจสอบข้อมูล
    if (empty($booking_date) || empty($booking_time) || empty($pickup_address)) {
        $_SESSION['error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        header("Location: ../bookings.php");
        exit();
    }

    // ตรวจสอบว่ามีการเลือกประเภทขยะและระบุน้ำหนัก
    if (empty($selected_types) || count($selected_types) == 0) {
        $_SESSION['error'] = 'กรุณาเลือกประเภทขยะอย่างน้อย 1 รายการ';
        header("Location: ../bookings.php");
        exit();
    }

    // ตรวจสอบว่าทุกรายการที่เลือกมีน้ำหนัก
    $valid_items = [];
    $total_weight = 0;
    $total_price = 0;

    foreach ($selected_types as $type_id) {
        $type_id = intval($type_id);
        $weight = isset($weights[$type_id]) ? floatval($weights[$type_id]) : 0;

        if ($weight <= 0) {
            $_SESSION['error'] = 'กรุณาระบุน้ำหนักสำหรับทุกรายการที่เลือก';
            header("Location: ../bookings.php");
            exit();
        }

        // ดึงราคาปัจจุบัน
        $price_sql = "SELECT price_per_kg FROM prices WHERE type_id = '$type_id' AND is_current = 1 LIMIT 1";
        $price_result = mysqli_query($conn, $price_sql);

        if ($price_result && mysqli_num_rows($price_result) > 0) {
            $price_data = mysqli_fetch_assoc($price_result);
            $price_per_kg = $price_data['price_per_kg'];

            $valid_items[] = [
                'type_id' => $type_id,
                'weight' => $weight,
                'price_per_kg' => $price_per_kg
            ];

            $total_weight += $weight;
            $total_price += $weight * $price_per_kg;
        }
    }

    if (empty($valid_items)) {
        $_SESSION['error'] = 'ไม่พบรายการที่ถูกต้อง';
        header("Location: ../bookings.php");
        exit();
    }

    // ตรวจสอบวันที่ (ต้องจองล่วงหน้าอย่างน้อย 1 วัน)
    $booking_datetime = strtotime($booking_date);
    $tomorrow = strtotime('+1 day', strtotime(date('Y-m-d')));
    if ($booking_datetime < $tomorrow) {
        $_SESSION['error'] = 'กรุณาจองล่วงหน้าอย่างน้อย 1 วัน';
        header("Location: ../bookings.php");
        exit();
    }

    // เริ่ม transaction
    mysqli_begin_transaction($conn);

    try {
        // สร้างการจอง
        $insert_booking_sql = "INSERT INTO bookings
                              (user_id, booking_date, booking_time, pickup_address, estimated_weight, estimated_price, status, notes)
                              VALUES
                              ('$user_id', '$booking_date', '$booking_time', '$pickup_address', '$total_weight', '$total_price', 'pending', '$notes')";

        if (!mysqli_query($conn, $insert_booking_sql)) {
            throw new Exception('ไม่สามารถสร้างการจองได้');
        }

        $booking_id = mysqli_insert_id($conn);

        // เพิ่มรายการขยะในการจอง
        foreach ($valid_items as $item) {
            $type_id = $item['type_id'];
            $weight = $item['weight'];
            $price_per_kg = $item['price_per_kg'];

            $insert_item_sql = "INSERT INTO booking_items (booking_id, type_id, quantity, price_per_kg)
                               VALUES ('$booking_id', '$type_id', '$weight', '$price_per_kg')";

            if (!mysqli_query($conn, $insert_item_sql)) {
                throw new Exception('ไม่สามารถบันทึกรายการขยะได้');
            }
        }

        // สร้างการแจ้งเตือน
        $notification_title = 'การจองใหม่';
        $notification_message = "คุณได้สร้างการจองหมายเลข #" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . " สำเร็จ วันที่ " . date('d/m/Y', strtotime($booking_date));
        $notification_sql = "INSERT INTO notifications (user_id, title, message, type, is_read)
                            VALUES ('$user_id', '$notification_title', '$notification_message', 'booking', FALSE)";
        mysqli_query($conn, $notification_sql);

        // Commit transaction
        mysqli_commit($conn);

        $_SESSION['success'] = 'สร้างการจองสำเร็จ! เจ้าหน้าที่จะติดต่อกลับเพื่อยืนยันการนัดหมาย';
        header("Location: ../bookings.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);

        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        header("Location: ../bookings.php");
        exit();
    }
} else {
    header("Location: ../bookings.php");
    exit();
}