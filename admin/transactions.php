<?php
require_once '../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// ดึงข้อมูลธุรกรรม
$sql = "SELECT t.*,
        u.first_name, u.last_name, u.phone,
        b.booking_id, b.booking_date
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.user_id
        LEFT JOIN bookings b ON t.booking_id = b.booking_id
        ORDER BY t.created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการธุรกรรม - Green Digital</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>💳 จัดการธุรกรรม</h2>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php
                if($_GET['success'] == 'updated') echo 'อัปเดตสถานะธุรกรรมสำเร็จ';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>เลขที่การจอง</th>
                                <th>วันที่</th>
                                <th>ผู้ใช้</th>
                                <th>น้ำหนัก</th>
                                <th>ยอดเงิน</th>
                                <th>วิธีชำระ</th>
                                <th>สถานะ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result && mysqli_num_rows($result) > 0): ?>
                                <?php while($trans = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>#<?php echo $trans['transaction_id']; ?></td>
                                        <td>
                                            <a href="booking_detail.php?id=<?php echo $trans['booking_id']; ?>">
                                                #<?php echo str_pad($trans['booking_id'], 6, '0', STR_PAD_LEFT); ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($trans['first_name'] . ' ' . $trans['last_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($trans['phone']); ?></small>
                                        </td>
                                        <td><?php echo number_format($trans['total_weight'], 2); ?> kg</td>
                                        <td><strong><?php echo number_format($trans['total_amount'], 2); ?> ฿</strong></td>
                                        <td>
                                            <?php
                                            $payment_methods = [
                                                'cash' => 'เงินสด',
                                                'bank_transfer' => 'โอนเงิน',
                                                'promptpay' => 'พร้อมเพย์'
                                            ];
                                            echo $payment_methods[$trans['payment_method']] ?? $trans['payment_method'];
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($trans['payment_status'] == 'paid'): ?>
                                                <span class="badge bg-success">ชำระแล้ว</span>
                                            <?php elseif($trans['payment_status'] == 'pending'): ?>
                                                <span class="badge bg-warning">รอชำระ</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">ล้มเหลว</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="booking_detail.php?id=<?php echo $trans['booking_id']; ?>" class="btn btn-sm btn-info">ดูรายละเอียด</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">ไม่มีข้อมูลธุรกรรม</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
