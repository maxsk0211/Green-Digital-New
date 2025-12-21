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

// ตรวจสอบสิทธิ์การเข้าถึง
if ($_SESSION['role'] == 'staff') {
    if ($booking['assigned_to'] != $admin_id) {
        $_SESSION['error'] = 'คุณไม่มีสิทธิ์เข้าถึงการจองนี้';
        header("Location: bookings.php");
        exit();
    }
}

// ดึงรายการขยะ
$items = mysqli_query($conn, "SELECT bi.*, rt.type_name, rt.category, rt.co2_reduction
              FROM booking_items bi
              JOIN recycle_types rt ON bi.type_id = rt.type_id
              WHERE bi.booking_id = $booking_id");

$items_data = [];
$total_co2 = 0;
if (mysqli_num_rows($items) > 0) {
    while ($row = mysqli_fetch_assoc($items)) {
        $items_data[] = $row;
        $total_co2 += $row['quantity'] * $row['co2_reduction'];
    }
}

// ดึงข้อมูล transaction
$transaction = null;
if ($booking['status'] == 'completed') {
    $trans_result = mysqli_query($conn, "SELECT * FROM transactions WHERE booking_id = $booking_id LIMIT 1");
    if ($trans_result && mysqli_num_rows($trans_result) > 0) {
        $transaction = mysqli_fetch_assoc($trans_result);
    }
}

// ดึงรายชื่อ admin
$admins_result = mysqli_query($conn, "SELECT admin_id, full_name FROM admins ORDER BY full_name");
$admins = [];
while ($admin = mysqli_fetch_assoc($admins_result)) {
    $admins[] = $admin;
}

// ดึงประเภทขยะ
$types_result = mysqli_query($conn, "SELECT rt.type_id, rt.type_name, rt.category, p.price_per_kg
                      FROM recycle_types rt
                      LEFT JOIN prices p ON rt.type_id = p.type_id AND p.is_current = TRUE
                      WHERE rt.status = 'active'
                      ORDER BY rt.category, rt.type_name");
$recycle_types = [];
while ($type = mysqli_fetch_assoc($types_result)) {
    $recycle_types[] = $type;
}

// Config สำหรับ status
$status_config = [
    'pending' => ['class' => 'bg-warning', 'text' => 'รอดำเนินการ'],
    'confirmed' => ['class' => 'bg-info', 'text' => 'ยืนยันแล้ว'],
    'completed' => ['class' => 'bg-success', 'text' => 'เสร็จสิ้น'],
    'cancelled' => ['class' => 'bg-danger', 'text' => 'ยกเลิก']
];

$payment_methods = [
    'cash' => 'เงินสด',
    'bank_transfer' => 'โอนเงิน',
    'promptpay' => 'พร้อมเพย์'
];
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
        <!-- Alert -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ปุ่มกลับ -->
        <div class="mb-3">
            <a href="bookings.php" class="btn btn-secondary">← กลับ</a>
        </div>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>การจอง #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></h2>
                <p class="text-muted mb-0">สร้างเมื่อ: <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?> น.</p>
            </div>
            <span class="badge <?php echo $status_config[$booking['status']]['class']; ?> fs-5">
                <?php echo $status_config[$booking['status']]['text']; ?>
            </span>
        </div>

        <div class="row">
            <!-- ฝั่งซ้าย -->
            <div class="col-lg-8">
                <!-- สรุปภาพรวม -->
                <div class="card bg-success text-white mb-3">
                    <div class="card-body">
                        <h5 class="text-white">สรุปภาพรวม</h5>
                        <div class="row text-center mt-3">
                            <div class="col-md-4">
                                <h3><?php echo count($items_data); ?></h3>
                                <p class="mb-0">รายการขยะ</p>
                            </div>
                            <div class="col-md-4">
                                <h3><?php echo number_format($booking['total_weight'] ?? 0, 2); ?></h3>
                                <p class="mb-0">น้ำหนักรวม (kg)</p>
                            </div>
                            <div class="col-md-4">
                                <h3><?php echo number_format($booking['calculated_total'] ?? 0, 2); ?></h3>
                                <p class="mb-0">ราคารวม (฿)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ข้อมูลลูกค้า -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title text-success border-bottom border-success pb-2 mb-3">ข้อมูลลูกค้า</h5>
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
                </div>

                <!-- ข้อมูลการจอง -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title text-success border-bottom border-success pb-2 mb-3">ข้อมูลการจอง</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>วันที่รับ:</strong><br>
                                <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>เวลา:</strong><br>
                                <?php echo date('H:i', strtotime($booking['booking_time'])); ?> น.
                            </div>
                        </div>
                        <div class="mb-3">
                            <strong>ที่อยู่รับขยะ:</strong><br>
                            <?php echo nl2br(htmlspecialchars($booking['pickup_address'])); ?>
                        </div>
                        <?php if ($booking['notes']): ?>
                            <div class="mb-0">
                                <strong>หมายเหตุ:</strong><br>
                                <?php echo nl2br(htmlspecialchars($booking['notes'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- รายการขยะ -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title text-success border-bottom border-success pb-2 mb-3">รายการขยะ</h5>
                        <?php if (count($items_data) > 0): ?>
                            <?php
                            $total_subtotal = 0;
                            $can_edit = ($booking['status'] == 'confirmed');
                            ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-success">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="25%">ประเภทขยะ</th>
                                            <th width="12%">หมวดหมู่</th>
                                            <th width="12%" class="text-center">น้ำหนัก (kg)</th>
                                            <th width="12%" class="text-center">ราคา/kg</th>
                                            <th width="12%" class="text-end">ราคารวม</th>
                                            <th width="12%" class="text-center">CO₂ ลดได้</th>
                                            <?php if ($can_edit): ?>
                                                <th width="10%" class="text-center">จัดการ</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        foreach ($items_data as $item):
                                            $total_subtotal += $item['subtotal'];
                                        ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($item['type_name']); ?></strong></td>
                                                <td><span class="badge bg-secondary"><?php echo $item['category']; ?></span></td>
                                                <td class="text-center">
                                                    <?php if ($can_edit): ?>
                                                        <form method="POST" action="sql/booking_update_item.php" class="d-inline">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                            <input type="number" name="quantity" class="form-control form-control-sm d-inline-block"
                                                                   value="<?php echo $item['quantity']; ?>" step="0.01" min="0.01" style="width: 80px;" required>
                                                            <button type="submit" class="btn btn-sm btn-primary">บันทึก</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <strong><?php echo number_format($item['quantity'], 2); ?></strong>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?php echo number_format($item['price_per_kg'], 2); ?> ฿</span>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="text-success"><?php echo number_format($item['subtotal'], 2); ?> ฿</strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="text-info"><?php echo number_format($item['quantity'] * $item['co2_reduction'], 2); ?> kg</span>
                                                </td>
                                                <?php if ($can_edit): ?>
                                                    <td class="text-center">
                                                        <a href="sql/booking_delete_item.php?booking_id=<?php echo $booking_id; ?>&item_id=<?php echo $item['item_id']; ?>"
                                                           class="btn btn-sm btn-danger" onclick="return confirm('ต้องการลบรายการนี้?')">ลบ</a>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="5" class="text-end">รวมทั้งหมด:</th>
                                            <th class="text-end"><h5 class="mb-0 text-success"><?php echo number_format($total_subtotal, 2); ?> ฿</h5></th>
                                            <th class="text-center"><span class="badge bg-info"><?php echo number_format($total_co2, 2); ?> kg CO₂</span></th>
                                            <?php if ($can_edit): ?><th></th><?php endif; ?>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">ไม่พบรายการขยะ</p>
                        <?php endif; ?>

                        <!-- ฟอร์มเพิ่มรายการ -->
                        <?php if ($booking['status'] == 'confirmed'): ?>
                            <div class="mt-4 pt-3 border-top">
                                <h6 class="mb-3">เพิ่มรายการขยะใหม่</h6>
                                <form method="POST" action="sql/booking_add_item.php">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">ประเภทขยะ</label>
                                            <select name="type_id" class="form-select" required>
                                                <option value="">-- เลือกประเภทขยะ --</option>
                                                <?php foreach ($recycle_types as $type): ?>
                                                    <option value="<?php echo $type['type_id']; ?>">
                                                        [<?php echo htmlspecialchars($type['category']); ?>]
                                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                                        (<?php echo number_format($type['price_per_kg'], 2); ?> ฿/kg)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">น้ำหนัก (kg)</label>
                                            <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label d-block">&nbsp;</label>
                                            <button type="submit" class="btn btn-success w-100">เพิ่ม</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ข้อมูลธุรกรรม -->
                <?php if ($transaction): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title text-success border-bottom border-success pb-2 mb-3">ข้อมูลธุรกรรม</h5>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <strong>วันที่ทำธุรกรรม:</strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?> น.
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>วิธีการชำระเงิน:</strong><br>
                                    <?php echo $payment_methods[$transaction['payment_method']] ?? $transaction['payment_method']; ?>
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
                    </div>
                <?php endif; ?>
            </div>

            <!-- ฝั่งขวา -->
            <div class="col-lg-4">
                <!-- CO2 -->
                <div class="alert alert-info text-center mb-3">
                    <h6>ช่วยลดคาร์บอนฟุตพริ้นท์</h6>
                    <h2 class="text-success"><?php echo number_format($total_co2, 2); ?> kg</h2>
                    <p class="mb-0">CO₂</p>
                    <hr>
                    <p class="mb-0">เทียบเท่าปลูกต้นไม้ <strong><?php echo number_format($total_co2 / 20, 1); ?></strong> ต้น</p>
                </div>

                <!-- จัดการสถานะ -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title text-success border-bottom border-success pb-2 mb-3">จัดการสถานะ</h5>
                        <?php if ($booking['status'] == 'confirmed'): ?>
                            <div class="mb-3">
                                <p><strong>สถานะปัจจุบัน:</strong> <span class="badge bg-info">ยืนยันแล้ว</span></p>
                                <p class="text-muted small">กดปุ่มด้านล่างเพื่อไปหน้าสรุปและชำระเงิน</p>
                            </div>
                            <a href="booking_payment.php?id=<?php echo $booking_id; ?>" class="btn btn-success w-100">ไปหน้าชำระเงิน</a>
                        <?php else: ?>
                            <form method="POST" action="sql/booking_update_status.php">
                                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label">สถานะการจอง</label>
                                    <select name="status" class="form-select" <?php echo in_array($booking['status'], ['cancelled', 'completed']) ? 'disabled' : ''; ?>>
                                        <?php foreach ($status_config as $key => $config): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $booking['status'] == $key ? 'selected' : ''; ?>>
                                                <?php echo $config['text']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if (!in_array($booking['status'], ['cancelled', 'completed'])): ?>
                                    <button type="submit" class="btn btn-primary w-100">บันทึกสถานะ</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- มอบหมายงาน -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title text-success border-bottom border-success pb-2 mb-3">มอบหมายงาน</h5>
                        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'owner'): ?>
                            <form method="POST" action="sql/booking_assign.php">
                                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label">ผู้รับผิดชอบ</label>
                                    <select name="assigned_to" class="form-select">
                                        <option value="">ไม่มี</option>
                                        <?php foreach ($admins as $admin): ?>
                                            <option value="<?php echo $admin['admin_id']; ?>" <?php echo $booking['assigned_to'] == $admin['admin_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($admin['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100">มอบหมาย</button>
                            </form>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">ผู้รับผิดชอบ</label>
                                <input type="text" class="form-control" value="<?php echo $booking['assigned_name'] ?? 'ไม่มี'; ?>" readonly>
                            </div>
                            <p class="text-muted small mb-0">* พนักงานไม่สามารถแก้ไขได้</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
