<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ดึงข้อมูลการจอง
$booking_sql = "SELECT b.*,
                u.first_name, u.last_name, u.phone, u.email, u.address as user_address
                FROM bookings b
                JOIN users u ON b.user_id = u.user_id
                WHERE b.booking_id = ?";
$stmt = $conn->prepare($booking_sql);
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$booking_result = $stmt->get_result();

if (!$booking_result || $booking_result->num_rows == 0) {
    $_SESSION['error'] = 'ไม่พบการจองนี้';
    header("Location: bookings.php");
    exit();
}

$booking = $booking_result->fetch_assoc();

// เช็คว่าสถานะเป็น confirmed หรือไม่
if ($booking['status'] != 'confirmed') {
    $_SESSION['error'] = 'ไม่สามารถชำระเงินได้ เนื่องจากสถานะไม่ใช่ "ยืนยันแล้ว"';
    header("Location: booking_detail.php?id=$booking_id");
    exit();
}

// ดึงรายการขยะในการจอง
$items_sql = "SELECT bi.*, rt.type_name, rt.category, rt.co2_reduction
              FROM booking_items bi
              JOIN recycle_types rt ON bi.type_id = rt.type_id
              WHERE bi.booking_id = ?";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$items = $stmt->get_result();

// คำนวณยอดรวม
$total_weight = 0;
$total_amount = 0;
$total_co2 = 0;
$items_data = [];

if ($items->num_rows > 0) {
    while ($item = $items->fetch_assoc()) {
        $total_weight += $item['quantity'];
        $total_amount += $item['subtotal'];
        $total_co2 += $item['quantity'] * $item['co2_reduction'];
        $items_data[] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปการชำระเงิน #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?> - Green Digital Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4 mb-5">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="booking_detail.php?id=<?php echo $booking_id; ?>" class="btn btn-sm btn-outline-secondary">← กลับ</a>
        </div>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2>💰 สรุปการชำระเงิน</h2>
                <p class="text-muted mb-0">การจอง #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Customer Info -->
                <div class="detail-card">
                    <h5 class="mb-3">👤 ข้อมูลลูกค้า</h5>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>ชื่อ-นามสกุล:</strong><br>
                            <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>เบอร์โทร:</strong><br>
                            <?php echo htmlspecialchars($booking['phone']); ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>ที่อยู่รับขยะ:</strong><br>
                            <?php echo nl2br(htmlspecialchars($booking['pickup_address'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Items List -->
                <div class="detail-card">
                    <h5 class="mb-3">♻️ รายการขยะ</h5>
                    <?php if (count($items_data) > 0): ?>
                        <?php foreach ($items_data as $item): ?>
                            <div class="item-row">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['type_name']); ?></h6>
                                        <span class="badge bg-secondary text-uppercase"><?php echo $item['category']; ?></span>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success"><?php echo number_format($item['price_per_kg'], 2); ?> ฿/kg</span>
                                    </div>
                                </div>
                                <div class="row text-center mt-2">
                                    <div class="col-4">
                                        <small class="text-muted">น้ำหนัก</small><br>
                                        <strong><?php echo number_format($item['quantity'], 2); ?> kg</strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">ราคารวม</small><br>
                                        <strong class="text-success"><?php echo number_format($item['subtotal'], 2); ?> ฿</strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">CO2 ลดได้</small><br>
                                        <strong class="text-info"><?php echo number_format($item['quantity'] * $item['co2_reduction'], 2); ?> kg</strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">ไม่พบรายการขยะ</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Summary -->
                <div class="summary-card">
                    <h4 class="mb-4">📊 สรุปยอดรวม</h4>
                    <div class="summary-item">
                        <span>น้ำหนักรวม:</span>
                        <span><?php echo number_format($total_weight, 2); ?> kg</span>
                    </div>
                    <div class="summary-item">
                        <span>CO2 ลดได้:</span>
                        <span><?php echo number_format($total_co2, 2); ?> kg</span>
                    </div>
                    <div class="summary-item">
                        <span>ยอดรวมทั้งหมด:</span>
                        <span><?php echo number_format($total_amount, 2); ?> ฿</span>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="detail-card">
                    <h5 class="mb-3">💳 ชำระเงิน</h5>
                    <form method="POST" action="sql/booking_complete_payment.php">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        <input type="hidden" name="total_weight" value="<?php echo $total_weight; ?>">
                        <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">

                        <div class="mb-3">
                            <label class="form-label">วิธีการชำระเงิน</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">-- เลือกวิธีชำระ --</option>
                                <option value="cash">เงินสด</option>
                                <option value="bank_transfer">โอนเงิน</option>
                                <option value="promptpay">พร้อมเพย์</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">สถานะการชำระ</label>
                            <select name="payment_status" class="form-select" required>
                                <option value="paid">ชำระแล้ว</option>
                                <option value="pending">รอชำระ</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">หมายเหตุ (ถ้ามี)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="ระบุหมายเหตุเพิ่มเติม..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 mb-2">✅ ยืนยันการชำระเงิน</button>
                        <a href="booking_detail.php?id=<?php echo $booking_id; ?>" class="btn btn-outline-secondary w-100">ยกเลิก</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
