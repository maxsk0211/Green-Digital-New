<?php
require_once 'config.php';

// ตรวจสอบว่าเป็นสมาชิก
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลสมาชิก
$user_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// สถิติต่างๆ
$bookings_result = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id = '$user_id'");
$bookings = $bookings_result ? mysqli_fetch_assoc($bookings_result)['c'] : 0;

$transactions_result = mysqli_query($conn, "SELECT COUNT(*) as c FROM transactions WHERE user_id = '$user_id'");
$transactions = $transactions_result ? mysqli_fetch_assoc($transactions_result)['c'] : 0;

$income_result = mysqli_query($conn, "SELECT SUM(total_amount) AS total FROM transactions WHERE user_id = '$user_id' and payment_status = 'paid'");
$total_income = $income_result ? mysqli_fetch_assoc($income_result)['total'] : 0;

$co2_result = mysqli_query($conn, "SELECT COALESCE(SUM(co2_reduced), 0) as total FROM carbon_footprint WHERE user_id = '$user_id'");
$co2_reduced = $co2_result ? mysqli_fetch_assoc($co2_result)['total'] : 0;

// การจองล่าสุด
$recent_bookings_sql = "SELECT * FROM bookings WHERE user_id = '$user_id' ORDER BY booking_date DESC LIMIT 5";
$recent_bookings = mysqli_query($conn, $recent_bookings_sql);

// ธุรกรรมล่าสุด
$recent_transactions_sql = "SELECT * FROM transactions WHERE user_id = '$user_id' ORDER BY transaction_date DESC LIMIT 5";
$recent_transactions = mysqli_query($conn, $recent_transactions_sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Green Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3>สวัสดี, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> 👋</h3>
                        <p class="mb-2">ระดับสมาชิก:
                            <?php
                            $badge_class = 'bg-secondary';
                            if($user['user_level'] == 'Silver') $badge_class = 'bg-light text-dark';
                            if($user['user_level'] == 'Gold') $badge_class = 'bg-warning text-dark';
                            ?>
                            <span class="badge <?php echo $badge_class; ?> fs-6"><?php echo $user['user_level']; ?></span>
                        </p>
                        <p class="mb-0">แต้มสะสม: <strong><?php echo number_format($user['total_points']); ?></strong> แต้ม</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6>📅 การจองทั้งหมด</h6>
                        <div class="display-6 fw-bold"><?php echo number_format($bookings); ?></div>
                        <small class="text-muted">ครั้ง</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6>💰 รายได้ทั้งหมด</h6>
                        <div class="display-6 fw-bold text-success"><?php echo number_format($total_income, 2); ?></div>
                        <small class="text-muted">บาท</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6>♻️ ธุรกรรม</h6>
                        <div class="display-6 fw-bold"><?php echo number_format($transactions); ?></div>
                        <small class="text-muted">ครั้ง</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6>🌱 CO2 ที่ช่วยลด</h6>
                        <div class="display-6 fw-bold text-success"><?php echo number_format($co2_reduced, 2); ?></div>
                        <small class="text-muted">kg</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">📅 การจองล่าสุด</h5>
                    </div>
                    <div class="card-body">
                        <?php if($recent_bookings && mysqli_num_rows($recent_bookings) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>วันที่</th>
                                            <th>เวลา</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($booking = mysqli_fetch_assoc($recent_bookings)): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                                                <td><?php echo date('H:i', strtotime($booking['booking_time'])); ?></td>
                                                <td>
                                                    <?php if($booking['status'] == 'pending'): ?>
                                                        <span class="badge bg-warning">รอดำเนินการ</span>
                                                    <?php elseif($booking['status'] == 'confirmed'): ?>
                                                        <span class="badge bg-info">ยืนยันแล้ว</span>
                                                    <?php elseif($booking['status'] == 'completed'): ?>
                                                        <span class="badge bg-success">เสร็จสิ้น</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">ยกเลิก</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="bookings.php" class="btn btn-sm btn-primary">ดูทั้งหมด</a>
                        <?php else: ?>
                            <p class="text-muted mb-0">ยังไม่มีการจอง</p>
                            <a href="bookings.php" class="btn btn-sm btn-primary mt-2">จองเลย</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">💳 ธุรกรรมล่าสุด</h5>
                    </div>
                    <div class="card-body">
                        <?php if($recent_transactions && mysqli_num_rows($recent_transactions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>วันที่</th>
                                            <th class="text-end">จำนวนเงิน</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($trans = mysqli_fetch_assoc($recent_transactions)): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($trans['transaction_date'])); ?></td>
                                                <td class="text-end"><?php echo number_format($trans['final_amount'], 2); ?> ฿</td>
                                                <td>
                                                    <?php if($trans['status'] == 'pending'): ?>
                                                        <span class="badge bg-warning">รอดำเนินการ</span>
                                                    <?php elseif($trans['status'] == 'completed'): ?>
                                                        <span class="badge bg-success">สำเร็จ</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">ยกเลิก</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="transactions.php" class="btn btn-sm btn-primary">ดูทั้งหมด</a>
                        <?php else: ?>
                            <p class="text-muted mb-0">ยังไม่มีธุรกรรม</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">⚡ เมนูด่วน</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="bookings.php" class="btn btn-outline-primary w-100">
                            <i>📅</i> จองรับขยะ
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="transactions.php" class="btn btn-outline-success w-100">
                            <i>💳</i> ประวัติธุรกรรม
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="points.php" class="btn btn-outline-warning w-100">
                            <i>⭐</i> แต้มสะสม
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="profile.php" class="btn btn-outline-info w-100">
                            <i>👤</i> โปรไฟล์
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
