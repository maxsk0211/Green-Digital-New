<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// ดึงข้อมูลสถิติ
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$total_users = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings");
$total_bookings = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE status='pending'");
$pending_bookings = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM transactions WHERE payment_status='paid'");
$total_revenue = $result ? mysqli_fetch_assoc($result)['total'] : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Green Digital Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="container mt-4">
        <h3 class="mb-4 border-bottom pb-2">📊 สถิติภาพรวม</h3>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6>จำนวนสมาชิก</h6>
                        <div class="display-6 fw-bold"><?php echo number_format($total_users); ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6>การจองทั้งหมด</h6>
                        <div class="display-6 fw-bold text-info"><?php echo number_format($total_bookings); ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6>รอดำเนินการ</h6>
                        <div class="display-6 fw-bold text-warning"><?php echo number_format($pending_bookings); ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6>รายได้รวม</h6>
                        <div class="display-6 fw-bold text-secondary">฿<?php echo number_format($total_revenue, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mb-4 border-bottom pb-2">📋 เมนูจัดการ</h3>

        <!-- Menu Cards -->
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <a href="bookings.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center position-relative">
                        <div class="display-4 mb-3">📅</div>
                        <h5>จัดการการจอง</h5>
                        <?php if ($pending_bookings > 0): ?>
                            <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-danger">
                                <?php echo $pending_bookings; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="admins.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <div class="display-4 mb-3">👨‍💼</div>
                        <h5>จัดการผู้ดูแล</h5>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="users.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <div class="display-4 mb-3">👥</div>
                        <h5>จัดการสมาชิก</h5>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="recycle_types.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <div class="display-4 mb-3">♻️</div>
                        <h5>ประเภทขยะ</h5>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="prices.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <div class="display-4 mb-3">💰</div>
                        <h5>จัดการราคา</h5>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="transactions.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <div class="display-4 mb-3">💳</div>
                        <h5>ธุรกรรม</h5>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="articles.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <div class="display-4 mb-3">📚</div>
                        <h5>บทความ</h5>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="promotions.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <div class="display-4 mb-3">🎁</div>
                        <h5>โปรโมชั่น</h5>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="reports.php" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <div class="display-4 mb-3">📊</div>
                        <h5>รายงาน</h5>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
