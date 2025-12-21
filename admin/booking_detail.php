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
$booking_result = mysqli_query($conn, "SELECT b.*,
                u.first_name, u.last_name, u.phone, u.email, u.address as user_address, u.user_level,
                a.full_name as assigned_name,
                (SELECT SUM(quantity) FROM booking_items WHERE booking_id = b.booking_id) as total_weight,
                (SELECT SUM(subtotal) FROM booking_items WHERE booking_id = b.booking_id) as calculated_total
                FROM bookings b
                JOIN users u ON b.user_id = u.user_id
                LEFT JOIN admins a ON b.assigned_to = a.admin_id
                WHERE b.booking_id = $booking_id");

if (!$booking_result || mysqli_num_rows($booking_result) == 0) {
    $_SESSION['error'] = 'ไม่พบการจองนี้';
    header("Location: bookings.php");
    exit();
}

$booking = mysqli_fetch_assoc($booking_result);

// ตรวจสอบสิทธิ์การเข้าถึง: พนักงานเห็นเฉพาะงานที่ได้รับมอบหมาย
if ($_SESSION['role'] == 'staff') {
    if ($booking['assigned_to'] != $admin_id) {
        $_SESSION['error'] = 'คุณไม่มีสิทธิ์เข้าถึงการจองนี้';
        header("Location: bookings.php");
        exit();
    }
}

// ดึงรายการขยะในการจอง
$items = mysqli_query($conn, "SELECT bi.*, rt.type_name, rt.category, rt.co2_reduction
              FROM booking_items bi
              JOIN recycle_types rt ON bi.type_id = rt.type_id
              WHERE bi.booking_id = $booking_id");

// คำนวณ CO2
$total_co2 = 0;
if (mysqli_num_rows($items) > 0) {
    $items_data = mysqli_fetch_all($items, MYSQLI_ASSOC);
    foreach ($items_data as $item) {
        $total_co2 += $item['quantity'] * $item['co2_reduction'];
    }
}

// ดึงข้อมูล transaction ถ้ามี
$transaction = null;
if ($booking['status'] == 'completed') {
    $trans_result = mysqli_query($conn, "SELECT * FROM transactions WHERE booking_id = $booking_id LIMIT 1");
    if ($trans_result && mysqli_num_rows($trans_result) > 0) {
        $transaction = mysqli_fetch_assoc($trans_result);
    }
}

// ดึงรายชื่อ admin ทั้งหมด
$admins = mysqli_query($conn, "SELECT admin_id, full_name, role FROM admins ORDER BY full_name");

// ดึงประเภทขยะทั้งหมดสำหรับ dropdown เพิ่มรายการ (JOIN กับ prices)
$recycle_types = mysqli_query($conn, "SELECT rt.type_id, rt.type_name, rt.category, p.price_per_kg
                      FROM recycle_types rt
                      LEFT JOIN prices p ON rt.type_id = p.type_id AND p.is_current = TRUE
                      WHERE rt.status = 'active'
                      ORDER BY rt.category, rt.type_name");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดการจอง #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?> - Green Digital Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4 mb-5">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="mb-3">
            <a href="bookings.php" class="btn btn-sm btn-outline-secondary">← กลับ</a>
        </div>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2>การจอง #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></h2>
                        <p class="text-muted mb-0">
                            สร้างเมื่อ: <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?> น.
                        </p>
                    </div>
                    <div>
                        <?php if ($booking['status'] == 'pending'): ?>
                            <span class="badge bg-warning fs-5">รอดำเนินการ</span>
                        <?php elseif ($booking['status'] == 'confirmed'): ?>
                            <span class="badge bg-info fs-5">ยืนยันแล้ว</span>
                        <?php elseif ($booking['status'] == 'completed'): ?>
                            <span class="badge bg-success fs-5">เสร็จสิ้น</span>
                        <?php else: ?>
                            <span class="badge bg-danger fs-5">ยกเลิก</span>
                        <?php endif; ?>
                    </div>
                </div>
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
                            <span class="badge bg-secondary ms-2"><?php echo $booking['user_level']; ?></span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>เบอร์โทร:</strong><br>
                            <a href="tel:<?php echo $booking['phone']; ?>"><?php echo htmlspecialchars($booking['phone']); ?></a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>อีเมล:</strong><br>
                            <a href="mailto:<?php echo $booking['email']; ?>"><?php echo htmlspecialchars($booking['email']); ?></a>
                        </div>
                    </div>
                </div>

                <!-- Booking Info -->
                <div class="detail-card">
                    <h5 class="mb-3">📋 ข้อมูลการจอง</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>📅 วันที่รับ:</strong><br>
                            <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>⏰ เวลา:</strong><br>
                            <?php echo date('H:i', strtotime($booking['booking_time'])); ?> น.
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>📍 ที่อยู่รับขยะ:</strong><br>
                        <?php echo nl2br(htmlspecialchars($booking['pickup_address'])); ?>
                    </div>
                    <?php if ($booking['notes']): ?>
                        <div class="mb-0">
                            <strong>📝 หมายเหตุ:</strong><br>
                            <?php echo nl2br(htmlspecialchars($booking['notes'])); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Items -->
                <div class="detail-card">
                    <h5 class="mb-3">♻️ รายการขยะ</h5>
                    <?php if (isset($items_data) && count($items_data) > 0): ?>
                        <?php
                        $total_subtotal = 0;
                        $can_edit = ($booking['status'] == 'confirmed');
                        foreach ($items_data as $item):
                            $total_subtotal += $item['subtotal'];
                        ?>
                            <div class="item-detail">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['type_name']); ?></h6>
                                        <span class="badge bg-secondary text-uppercase"><?php echo $item['category']; ?></span>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success"><?php echo number_format($item['price_per_kg'], 2); ?> ฿/kg</span>
                                    </div>
                                </div>

                                <?php if ($can_edit): ?>
                                    <!-- ฟอร์มแก้ไขน้ำหนัก -->
                                    <form method="POST" action="sql/booking_update_item.php" class="mb-2">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label mb-1"><small>น้ำหนัก (kg)</small></label>
                                                <input type="number" name="quantity" class="form-control form-control-sm"
                                                       value="<?php echo $item['quantity']; ?>"
                                                       step="0.01" min="0.01" required>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted">ราคารวม</small><br>
                                                <strong class="text-success"><?php echo number_format($item['subtotal'], 2); ?> ฿</strong>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-primary btn-sm w-100">💾 บันทึก</button>
                                            </div>
                                        </div>
                                    </form>
                                    <!-- ปุ่มลบ -->
                                    <div class="text-end">
                                        <a href="sql/booking_delete_item.php?booking_id=<?php echo $booking_id; ?>&item_id=<?php echo $item['item_id']; ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('ต้องการลบรายการนี้ใช่หรือไม่?')">
                                            🗑️ ลบรายการ
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <!-- แสดงแบบ Read-only -->
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
                                <?php endif; ?>

                                <div class="text-center mt-2">
                                    <small class="text-muted">CO2 ลดได้: </small>
                                    <strong class="text-info"><?php echo number_format($item['quantity'] * $item['co2_reduction'], 2); ?> kg</strong>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Summary -->
                        <div class="mt-3 pt-3 border-top">
                            <div class="row">
                                <div class="col-6">
                                    <h6>น้ำหนักรวม:</h6>
                                    <h4><?php echo number_format($booking['total_weight'], 2); ?> kg</h4>
                                </div>
                                <div class="col-6 text-end">
                                    <h6>ราคารวม:</h6>
                                    <h4 class="text-success"><?php echo number_format($total_subtotal, 2); ?> ฿</h4>
                                </div>
                            </div>
                            <div class="text-center mt-2">
                                <span class="badge bg-info fs-6">🌱 ช่วยลด CO2: <?php echo number_format($total_co2, 2); ?> kg</span>
                            </div>
                        </div>

                        <!-- ฟอร์มเพิ่มรายการใหม่ -->
                        <?php if ($can_edit): ?>
                            <div class="mt-4 pt-3 border-top">
                                <h6 class="mb-3">➕ เพิ่มรายการขยะใหม่</h6>
                                <form method="POST" action="sql/booking_add_item.php">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">ประเภทขยะ</label>
                                            <select name="type_id" class="form-select" required>
                                                <option value="">-- เลือกประเภทขยะ --</option>
                                                <?php
                                                if ($recycle_types && mysqli_num_rows($recycle_types) > 0):
                                                    mysqli_data_seek($recycle_types, 0);
                                                    while ($type = mysqli_fetch_assoc($recycle_types)):
                                                ?>
                                                    <option value="<?php echo $type['type_id']; ?>">
                                                        [<?php echo htmlspecialchars($type['category']); ?>]
                                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                                        (<?php echo number_format($type['price_per_kg'], 2); ?> ฿/kg)
                                                    </option>
                                                <?php
                                                    endwhile;
                                                endif;
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">น้ำหนัก (kg)</label>
                                            <input type="number" name="quantity" class="form-control"
                                                   step="0.01" min="0.01" placeholder="0.00" required>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label d-block">&nbsp;</label>
                                            <button type="submit" class="btn btn-success w-100">➕ เพิ่ม</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <p class="text-muted mb-0">ไม่พบรายการขยะ</p>

                        <!-- ฟอร์มเพิ่มรายการใหม่ (กรณีไม่มีรายการเลย) -->
                        <?php if ($booking['status'] == 'confirmed'): ?>
                            <div class="mt-3 pt-3 border-top">
                                <h6 class="mb-3">➕ เพิ่มรายการขยะใหม่</h6>
                                <form method="POST" action="sql/booking_add_item.php">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">ประเภทขยะ</label>
                                            <select name="type_id" class="form-select" required>
                                                <option value="">-- เลือกประเภทขยะ --</option>
                                                <?php
                                                if ($recycle_types && mysqli_num_rows($recycle_types) > 0):
                                                    mysqli_data_seek($recycle_types, 0);
                                                    while ($type = mysqli_fetch_assoc($recycle_types)):
                                                ?>
                                                    <option value="<?php echo $type['type_id']; ?>">
                                                        [<?php echo htmlspecialchars($type['category']); ?>]
                                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                                        (<?php echo number_format($type['price_per_kg'], 2); ?> ฿/kg)
                                                    </option>
                                                <?php
                                                    endwhile;
                                                endif;
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">น้ำหนัก (kg)</label>
                                            <input type="number" name="quantity" class="form-control"
                                                   step="0.01" min="0.01" placeholder="0.00" required>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label d-block">&nbsp;</label>
                                            <button type="submit" class="btn btn-success w-100">➕ เพิ่ม</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Transaction Info -->
                <?php if ($transaction): ?>
                    <div class="detail-card">
                        <h5 class="mb-3">💳 ข้อมูลธุรกรรม</h5>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <strong>วันที่ทำธุรกรรม:</strong><br>
                                <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?> น.
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>วิธีการชำระเงิน:</strong><br>
                                <?php
                                $payment_methods = [
                                    'cash' => 'เงินสด',
                                    'bank_transfer' => 'โอนเงิน',
                                    'promptpay' => 'พร้อมเพย์'
                                ];
                                echo $payment_methods[$transaction['payment_method']] ?? $transaction['payment_method'];
                                ?>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>สถานะการชำระเงิน:</strong><br>
                                <?php if ($transaction['payment_status'] == 'paid'): ?>
                                    <span class="badge bg-success">ชำระแล้ว</span>
                                <?php elseif ($transaction['payment_status'] == 'pending'): ?>
                                    <span class="badge bg-warning">รอชำระ</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">ล้มเหลว</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>จำนวนเงิน:</strong><br>
                                <span class="text-success fs-5"><?php echo number_format($transaction['total_amount'], 2); ?> ฿</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Status Management -->
                <div class="detail-card">
                    <h5 class="mb-3">⚙️ จัดการสถานะ</h5>

                    <?php if ($booking['status'] == 'confirmed'): ?>
                        <!-- ถ้าเป็น confirmed แสดงปุ่มไปหน้าชำระเงินโดยตรง -->
                        <div class="mb-3">
                            <p><strong>สถานะปัจจุบัน:</strong> <span class="badge bg-info">ยืนยันแล้ว</span></p>
                            <p class="text-muted small">กดปุ่มด้านล่างเพื่อไปหน้าสรุปและชำระเงิน</p>
                        </div>
                        <a href="booking_payment.php?id=<?php echo $booking_id; ?>" class="btn btn-success w-100">
                            💰 ไปหน้าชำระเงิน
                        </a>
                    <?php else: ?>
                        <!-- ฟอร์มเปลี่ยนสถานะปกติ -->
                        <form method="POST" action="sql/booking_update_status.php">
                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                            <div class="mb-3">
                                <label class="form-label">สถานะการจอง</label>
                                <select name="status" class="form-select" <?php echo $booking['status'] == 'cancelled' || $booking['status'] == 'completed' ? 'disabled' : ''; ?>>
                                    <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                    <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>ยืนยันแล้ว</option>
                                    <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                                    <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                                </select>
                            </div>
                            <?php if ($booking['status'] != 'cancelled' && $booking['status'] != 'completed'): ?>
                                <button type="submit" class="btn btn-primary w-100">💾 บันทึกสถานะ</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Assign Admin -->
                <div class="detail-card">
                    <h5 class="mb-3">👨‍💼 มอบหมายงาน</h5>
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'owner'): ?>
                        <!-- ฟอร์มแก้ไขได้ (แอดมิน/เจ้าของร้าน) -->
                        <form method="POST" action="sql/booking_assign.php">
                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                            <div class="mb-3">
                                <label class="form-label">ผู้รับผิดชอบ</label>
                                <select name="assigned_to" class="form-select">
                                    <option value="">ไม่มี</option>
                                    <?php
                                    mysqli_data_seek($admins, 0);
                                    while ($admin = mysqli_fetch_assoc($admins)):
                                    ?>
                                        <option value="<?php echo $admin['admin_id']; ?>"
                                                <?php echo $booking['assigned_to'] == $admin['admin_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($admin['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100">✅ มอบหมาย</button>
                        </form>
                    <?php else: ?>
                        <!-- แสดงแบบ Read-only (พนักงาน) -->
                        <div class="mb-3">
                            <label class="form-label">ผู้รับผิดชอบ</label>
                            <input type="text" class="form-control"
                                   value="<?php echo $booking['assigned_name'] ? htmlspecialchars($booking['assigned_name']) : 'ไม่มี'; ?>"
                                   readonly>
                        </div>
                        <p class="text-muted small mb-0">* พนักงานไม่สามารถแก้ไขได้</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
