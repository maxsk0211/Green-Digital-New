<?php
require_once 'config.php';

// ตรวจสอบว่าเป็นสมาชิก
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ดึงข้อมูลการจอง
$booking_sql = "SELECT b.* FROM bookings b
                WHERE b.booking_id = '$booking_id' AND b.user_id = '$user_id'";
$booking_result = mysqli_query($conn, $booking_sql);

if (!$booking_result || mysqli_num_rows($booking_result) == 0) {
    $_SESSION['error'] = 'ไม่พบการจองนี้';
    header("Location: bookings.php");
    exit();
}

$booking = mysqli_fetch_assoc($booking_result);

// ตรวจสอบสถานะ
if ($booking['status'] != 'pending' && $booking['status'] != 'confirmed') {
    $_SESSION['error'] = 'ไม่สามารถยกเลิกการจองนี้ได้';
    header("Location: bookings.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันการยกเลิก - Green Digital</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">⚠️ ยืนยันการยกเลิกการจอง</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>คุณกำลังจะยกเลิกการจอง:</strong>
                        </div>

                        <div class="mb-3">
                            <p><strong>หมายเลขจอง:</strong> #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></p>
                            <p><strong>วันที่:</strong> <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></p>
                            <p><strong>เวลา:</strong> <?php echo date('H:i', strtotime($booking['booking_time'])); ?> น.</p>
                            <p><strong>ที่อยู่:</strong> <?php echo nl2br(htmlspecialchars($booking['pickup_address'])); ?></p>
                        </div>

                        <div class="alert alert-info">
                            <small>
                                <strong>หมายเหตุ:</strong> การยกเลิกนี้ไม่สามารถย้อนกลับได้
                                หากต้องการจองใหม่ กรุณาทำการจองอีกครั้ง
                            </small>
                        </div>

                        <form method="POST" action="sql/booking_cancel.php">
                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="bookings.php" class="btn btn-secondary">ไม่ยกเลิก กลับหน้าจอง</a>
                                <button type="submit" class="btn btn-danger">✅ ยืนยันยกเลิกการจอง</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
