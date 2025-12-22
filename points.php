<?php
require_once 'config.php';

// ตรวจสอบว่าเป็นสมาชิก
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลสมาชิกและแต้มปัจจุบัน
$user_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);
$current_points = $user['points'] ?? 0;

// ดึงประวัติการทำรายการแต้มทั้งหมด
$points_sql = "SELECT pt.*,
               b.booking_id, b.booking_date, b.pickup_address
               FROM point_transactions pt
               LEFT JOIN bookings b ON pt.booking_id = b.booking_id
               WHERE pt.user_id = '$user_id'
               ORDER BY pt.created_at DESC";
$points_transactions = mysqli_query($conn, $points_sql);

// เก็บข้อมูลใน array
$transactions_data = [];
while ($pt = mysqli_fetch_assoc($points_transactions)) {
    $transactions_data[] = $pt;
}

// คำนวณสถิติ
$total_earned = 0;
$total_redeemed = 0;
foreach($transactions_data as $pt) {
    if ($pt['transaction_type'] == 'earn' || $pt['points'] > 0) {
        $total_earned += abs($pt['points']);
    } elseif ($pt['transaction_type'] == 'redeem' || $pt['points'] < 0) {
        $total_redeemed += abs($pt['points']);
    }
}
$total_transactions = count($transactions_data);

// Config ประเภทรายการ
$transaction_config = [
    'earn' => ['class' => 'bg-success', 'text' => 'ได้รับแต้ม'],
    'redeem' => ['class' => 'bg-danger', 'text' => 'ใช้แต้ม'],
    'adjustment' => ['class' => 'bg-warning', 'text' => 'ปรับปรุง']
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แต้มสะสม - Green Digital</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <strong>สำเร็จ!</strong> <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>ผิดพลาด!</strong> <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Points Hero Section -->
        <div class="card border-0 shadow mb-4 bg-success bg-gradient text-white">
            <div class="card-body text-center p-5">
                <h3 class="mb-3 opacity-75">แต้มสะสมของคุณ</h3>
                <h1 class="display-1 fw-bold mb-3"><?= number_format($current_points) ?></h1>
                <h4 class="mb-3">แต้ม</h4>
                <div class="badge bg-light text-dark fs-6 px-4 py-2">
                    100 บาท = 1 แต้ม
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <h2 class="display-5 fw-bold text-success mb-2">+<?= number_format($total_earned) ?></h2>
                        <p class="text-muted mb-0">แต้มที่ได้รับทั้งหมด</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <h2 class="display-5 fw-bold text-danger mb-2"><?= $total_redeemed > 0 ? '-' . number_format($total_redeemed) : number_format($total_redeemed) ?></h2>
                        <p class="text-muted mb-0">แต้มที่ใช้ไปแล้ว</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <h2 class="display-5 fw-bold text-primary mb-2"><?= number_format($total_transactions) ?></h2>
                        <p class="text-muted mb-0">จำนวนรายการทั้งหมด</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information Box -->
        <div class="card border-0 shadow-sm border-start border-success border-5 mb-4 bg-light">
            <div class="card-body p-4">
                <h5 class="mb-3 text-success">วิธีการได้รับแต้ม</h5>
                <ul class="mb-0 fs-6">
                    <li class="mb-2">รับแต้มทุกครั้งที่ขายขยะรีไซเคิล (100 บาท = 1 แต้ม)</li>
                    <li class="mb-2">แต้มจะได้รับเมื่อการจองเสร็จสิ้นและชำระเงินเรียบร้อยแล้ว</li>
                    <li>สามารถนำแต้มไปแลกของรางวัลหรือส่วนลดได้ในอนาคต</li>
                </ul>
            </div>
        </div>

        <!-- Transactions History -->
        <div class="row">
            <div class="col-12">
                <h4 class="mb-3">ประวัติการทำรายการแต้ม</h4>

                <?php if ($total_transactions > 0): ?>
                    <?php foreach($transactions_data as $pt): ?>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-3">
                                            <?php
                                            // กำหนดประเภทรายการ
                                            $trans_type = $pt['transaction_type'];
                                            if ($pt['points'] < 0) {
                                                $trans_type = 'redeem';
                                            } elseif ($pt['points'] > 0 && $trans_type != 'adjustment') {
                                                $trans_type = 'earn';
                                            }

                                            $config = $transaction_config[$trans_type] ?? $transaction_config['earn'];
                                            ?>
                                            <div>
                                                <span class="badge <?= $config['class'] ?> fs-6 px-3 py-2">
                                                    <?= $config['text'] ?>
                                                </span>
                                            </div>
                                        </div>

                                        <h5 class="mb-3 fw-bold"><?= htmlspecialchars($pt['description'] ?? 'รายการแต้ม') ?></h5>

                                        <div class="text-muted">
                                            <?php if ($pt['booking_id']): ?>
                                                <div class="mb-1"><strong>การจองหมายเลข:</strong> #<?= str_pad($pt['booking_id'], 6, '0', STR_PAD_LEFT) ?></div>
                                            <?php endif; ?>

                                            <?php if ($pt['amount'] > 0): ?>
                                                <div class="mb-1"><strong>ยอดเงิน:</strong> <?= number_format($pt['amount'], 2) ?> บาท</div>
                                            <?php endif; ?>

                                            <div><strong>วันที่:</strong> <?= date('d/m/Y H:i น.', strtotime($pt['created_at'])) ?></div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 text-end">
                                        <?php if ($pt['points'] > 0): ?>
                                            <h3 class="text-success fw-bold mb-0">
                                                +<?= number_format($pt['points']) ?>
                                            </h3>
                                            <span class="text-muted">แต้ม</span>
                                        <?php else: ?>
                                            <h3 class="text-danger fw-bold mb-0">
                                                <?= number_format($pt['points']) ?>
                                            </h3>
                                            <span class="text-muted">แต้ม</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="card border-0 shadow-sm text-center p-5">
                        <div class="card-body">
                            <h4 class="mb-3">ยังไม่มีรายการแต้มสะสม</h4>
                            <p class="text-muted mb-4">เริ่มต้นรับแต้มได้ด้วยการขายขยะรีไซเคิลกับเรา</p>
                            <a href="bookings.php" class="btn btn-primary shadow">
                                สร้างการจองใหม่
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
