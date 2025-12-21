<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// ตรวจสอบว่ามีการส่งข้อมูลมา
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = intval($_POST['user_id']);
    $reward_id = intval($_POST['reward_id']);
    $quantity = intval($_POST['quantity']);
    $delivery_method = $_POST['delivery_method'];
    $search = $_POST['search'] ?? '';

    // Validate quantity
    if ($quantity < 1) {
        header("Location: ../reward_redeem_for_user.php?search=" . urlencode($search) . "&error=invalid_quantity");
        exit();
    }

    // เริ่ม transaction
    mysqli_begin_transaction($conn);

    try {
        // 1. ดึงข้อมูลของรางวัล
        $reward_sql = "SELECT * FROM rewards WHERE reward_id = $reward_id AND status = 'active' FOR UPDATE";
        $reward_result = mysqli_query($conn, $reward_sql);

        if (mysqli_num_rows($reward_result) == 0) {
            throw new Exception("reward_not_found");
        }

        $reward = mysqli_fetch_assoc($reward_result);

        // 2. ตรวจสอบสต็อก
        if ($reward['stock_quantity'] > 0 && $reward['stock_quantity'] < $quantity) {
            throw new Exception("out_of_stock");
        }

        // 3. คำนวณแต้มที่ใช้
        $points_per_item = $reward['points_required'];
        $total_points = $points_per_item * $quantity;

        // 4. ดึงข้อมูลลูกค้า
        $user_sql = "SELECT * FROM users WHERE user_id = $user_id FOR UPDATE";
        $user_result = mysqli_query($conn, $user_sql);

        if (mysqli_num_rows($user_result) == 0) {
            throw new Exception("user_not_found");
        }

        $user = mysqli_fetch_assoc($user_result);
        $current_points = $user['points'] ?? 0;

        // 5. ตรวจสอบแต้มเพียงพอ
        if ($current_points < $total_points) {
            throw new Exception("insufficient_points");
        }

        // 6. หักแต้มของลูกค้า
        $new_points = $current_points - $total_points;
        $update_user_sql = "UPDATE users SET points = $new_points WHERE user_id = $user_id";
        if (!mysqli_query($conn, $update_user_sql)) {
            throw new Exception("update_user_failed");
        }

        // 7. บันทึกประวัติการแลก
        $redemption_sql = "INSERT INTO reward_redemptions
                          (user_id, reward_id, points_used, quantity, total_points, status,
                           delivery_method, redeemed_by, redemption_date)
                          VALUES
                          ($user_id, $reward_id, $points_per_item, $quantity, $total_points, 'completed',
                           '$delivery_method', $admin_id, NOW())";

        if (!mysqli_query($conn, $redemption_sql)) {
            throw new Exception("insert_redemption_failed");
        }

        $redemption_id = mysqli_insert_id($conn);

        // 8. บันทึกประวัติการใช้แต้ม
        $transaction_sql = "INSERT INTO point_transactions
                           (user_id, redemption_id, points, transaction_type, description, created_at)
                           VALUES
                           ($user_id, $redemption_id, -$total_points, 'redeem',
                            'แลกของรางวัล: {$reward['reward_name']} x{$quantity}', NOW())";

        if (!mysqli_query($conn, $transaction_sql)) {
            throw new Exception("insert_transaction_failed");
        }

        // 9. ลดสต็อก (ถ้ามีการจำกัดจำนวน)
        if ($reward['stock_quantity'] > 0) {
            $new_stock = $reward['stock_quantity'] - $quantity;
            $update_stock_sql = "UPDATE rewards SET stock_quantity = $new_stock WHERE reward_id = $reward_id";

            if (!mysqli_query($conn, $update_stock_sql)) {
                throw new Exception("update_stock_failed");
            }

            // ตรวจสอบว่าสินค้าหมดหรือไม่
            if ($new_stock == 0) {
                $update_status_sql = "UPDATE rewards SET status = 'out_of_stock' WHERE reward_id = $reward_id";
                mysqli_query($conn, $update_status_sql);
            }
        }

        // Commit transaction
        mysqli_commit($conn);

        // สำเร็จ - กลับไปหน้าเดิม
        header("Location: ../reward_redeem_for_user.php?search=" . urlencode($search) . "&success=1");
        exit();

    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);

        $error_message = $e->getMessage();
        header("Location: ../reward_redeem_for_user.php?search=" . urlencode($search) . "&error=" . $error_message);
        exit();
    }

} else {
    header("Location: ../reward_redeem_for_user.php");
    exit();
}
?>
