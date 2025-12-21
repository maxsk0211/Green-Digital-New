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

// ดึงข้อมูลธุรกรรมทั้งหมดของผู้ใช้
$transactions_sql = "SELECT t.*,
                     b.booking_id, b.booking_date, b.pickup_address,
                     (SELECT SUM(quantity) FROM booking_items WHERE booking_id = t.booking_id) as items_count
                     FROM transactions t
                     LEFT JOIN bookings b ON t.booking_id = b.booking_id
                     WHERE t.user_id = '$user_id'
                     ORDER BY t.created_at DESC";
$transactions = mysqli_query($conn, $transactions_sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติธุรกรรม - Green Digital</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <!-- Header -->
        <div class="card border-0 shadow mb-4 bg-primary bg-gradient text-white">
            <div class="card-body p-4">
                <h2 class="mb-2 fw-bold">💳 ประวัติธุรกรรม</h2>
                <p class="mb-0 opacity-75">รายการชำระเงินทั้งหมดของคุณ</p>
            </div>
        </div>

        <!-- Stats Summary -->
        <?php
        // คำนวณสถิติ
        mysqli_data_seek($transactions, 0);
        $total_transactions = mysqli_num_rows($transactions);
        $total_income = 0;
        $total_weight_all = 0;

        while ($t = mysqli_fetch_assoc($transactions)) {
            if ($t['payment_status'] == 'paid') {
                $total_income += $t['total_amount'];
                $total_weight_all += $t['total_weight'];
            }
        }
        mysqli_data_seek($transactions, 0);
        ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4 fw-bold">
                    <span class="text-primary">📊</span> สรุปสถิติธุรกรรม
                </h5>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded-3">
                            <div class="mb-2">
                                <span class="fs-2">📝</span>
                            </div>
                            <h2 class="display-6 fw-bold text-primary mb-2"><?php echo number_format($total_transactions); ?></h2>
                            <p class="text-muted mb-0 fw-bold">ธุรกรรมทั้งหมด</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded-3">
                            <div class="mb-2">
                                <span class="fs-2">💰</span>
                            </div>
                            <h2 class="display-6 fw-bold text-success mb-2"><?php echo number_format($total_income, 2); ?> ฿</h2>
                            <p class="text-muted mb-0 fw-bold">รายได้รวม</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded-3">
                            <div class="mb-2">
                                <span class="fs-2">⚖️</span>
                            </div>
                            <h2 class="display-6 fw-bold text-info mb-2"><?php echo number_format($total_weight_all, 2); ?> kg</h2>
                            <p class="text-muted mb-0 fw-bold">น้ำหนักรวม</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="row">
            <div class="col-12">
                <?php if (mysqli_num_rows($transactions) > 0): ?>
                    <?php while ($trans = mysqli_fetch_assoc($transactions)): ?>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <!-- Left: Transaction Info -->
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="mb-1 fw-bold">
                                                    การจอง #<?php echo str_pad($trans['booking_id'], 6, '0', STR_PAD_LEFT); ?>
                                                </h5>
                                                <small class="text-muted">
                                                    ธุรกรรม #<?php echo $trans['transaction_id']; ?> •
                                                    <?php echo date('d/m/Y H:i น.', strtotime($trans['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div>
                                                <?php if ($trans['payment_status'] == 'paid'): ?>
                                                    <span class="badge bg-success fs-6 px-3 py-2">ชำระแล้ว</span>
                                                <?php elseif ($trans['payment_status'] == 'pending'): ?>
                                                    <span class="badge bg-warning fs-6 px-3 py-2">รอชำระ</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger fs-6 px-3 py-2">ล้มเหลว</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-6 mb-2">
                                                <small class="text-muted d-block mb-1">วิธีการชำระเงิน</small>
                                                <span class="fw-bold">
                                                <?php
                                                $payment_methods = [
                                                    'cash' => '💵 เงินสด',
                                                    'bank_transfer' => '🏦 โอนเงิน',
                                                    'promptpay' => '📱 พร้อมเพย์'
                                                ];
                                                echo $payment_methods[$trans['payment_method']] ?? $trans['payment_method'];
                                                ?>
                                                </span>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <small class="text-muted d-block mb-1">น้ำหนักทั้งหมด</small>
                                                <span class="fw-bold"><?php echo number_format($trans['total_weight'], 2); ?> kg</span>
                                            </div>
                                        </div>

                                        <?php if ($trans['payment_date']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    วันที่ชำระ: <?php echo date('d/m/Y H:i น.', strtotime($trans['payment_date'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Right: Amount & Action -->
                                    <div class="col-md-4">
                                        <div class="card bg-success bg-gradient text-white border-0 shadow mb-3">
                                            <div class="card-body text-center p-3">
                                                <div class="opacity-75 mb-2">ยอดเงิน</div>
                                                <h2 class="fw-bold mb-0">
                                                    <?php echo number_format($trans['total_amount'], 2); ?> ฿
                                                </h2>
                                            </div>
                                        </div>
                                        <a href="booking_detail.php?id=<?php echo $trans['booking_id']; ?>"
                                           class="btn btn-outline-primary w-100 shadow-sm">
                                            ดูรายละเอียด
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="card border-0 shadow-sm text-center p-5">
                        <div class="card-body">
                            <div class="display-1 mb-4">💳</div>
                            <h4 class="mb-3">ยังไม่มีธุรกรรม</h4>
                            <p class="text-muted mb-4">เมื่อคุณทำการขายขยะรีไซเคิลกับเรา<br>ประวัติธุรกรรมจะแสดงที่นี่</p>
                            <a href="booking_create.php" class="btn btn-primary btn-lg shadow">
                                📝 จองรับซื้อขยะ
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Box -->
        <div class="alert alert-info mt-4">
            <strong>ℹ️ หมายเหตุ:</strong>
            <ul class="mb-0 mt-2">
                <li>ธุรกรรมจะถูกสร้างเมื่อพนักงานยืนยันการชำระเงินเสร็จสิ้น</li>
                <li>คุณสามารถดูรายละเอียดการจองและรายการขยะได้ที่หน้ารายละเอียด</li>
                <li>หากพบปัญหาเกี่ยวกับธุรกรรม กรุณาติดต่อเจ้าหน้าที่</li>
            </ul>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
