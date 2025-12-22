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

// ดึงรายการจองทั้งหมดของผู้ใช้
$bookings_sql = "SELECT b.*,
                 (SELECT SUM(quantity) FROM booking_items WHERE booking_id = b.booking_id) as total_weight
                 FROM bookings b
                 WHERE b.user_id = '$user_id'
                 ORDER BY b.booking_date DESC, b.booking_time DESC";
$bookings = mysqli_query($conn, $bookings_sql);

// เก็บข้อมูลใน array
$bookings_data = [];
while ($row = mysqli_fetch_assoc($bookings)) {
    $bookings_data[] = $row;
}

// Config สถานะ
$status_config = [
    'pending' => ['class' => 'bg-warning text-dark', 'text' => 'รอดำเนินการ'],
    'confirmed' => ['class' => 'bg-info', 'text' => 'ยืนยันแล้ว'],
    'completed' => ['class' => 'bg-success', 'text' => 'เสร็จสิ้น'],
    'cancelled' => ['class' => 'bg-danger', 'text' => 'ยกเลิก']
];

// Config หมวดหมู่
$category_names = [
    'plastic' => 'พลาสติก',
    'paper' => 'กระดาษ',
    'metal' => 'โลหะ',
    'glass' => 'แก้ว'
];

// ดึงประเภทขยะที่ใช้งาน
$recycle_types_sql = "SELECT rt.*, p.price_per_kg
                      FROM recycle_types rt
                      LEFT JOIN prices p ON rt.type_id = p.type_id AND p.is_current = 1
                      WHERE rt.status = 'active'
                      ORDER BY rt.category, rt.type_name";
$recycle_types = mysqli_query($conn, $recycle_types_sql);

// จัดกลุ่มตามหมวดหมู่
$types_by_category = [];
while ($type = mysqli_fetch_assoc($recycle_types)) {
    $types_by_category[$type['category']][] = $type;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองรับขยะ - Green Digital</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">

</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <a href="#" class="btn-close"></a>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <a href="#" class="btn-close"></a>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="card border-0 shadow mb-4 bg-success bg-gradient text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-2 fw-bold">การจองรับขยะ</h2>
                        <p class="mb-0 opacity-75">จัดการและติดตามการจองรับขยะรีไซเคิลของคุณ</p>
                    </div>
                    <button type="button" class="btn btn-light btn-lg shadow" data-bs-toggle="modal" data-bs-target="#newBookingModal">
                        จองใหม่
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">ทุกสถานะ</option>
                            <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                            <option value="confirmed" <?php echo isset($_GET['status']) && $_GET['status'] == 'confirmed' ? 'selected' : ''; ?>>ยืนยันแล้ว</option>
                            <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] == 'completed' ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                            <option value="cancelled" <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_from" class="form-control" placeholder="จากวันที่" value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_to" class="form-control" placeholder="ถึงวันที่" value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">ค้นหา</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings List -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="80">#</th>
                                <th>วันที่/เวลา</th>
                                <th>ที่อยู่รับขยะ</th>
                                <th width="120">น้ำหนักรวม</th>
                                <th width="120">ราคาประมาณ</th>
                                <th width="120">สถานะ</th>
                                <th width="180" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Apply filters
                            $filtered_bookings = [];
                            foreach($bookings_data as $booking) {
                                if (isset($_GET['status']) && $_GET['status'] != '' && $booking['status'] != $_GET['status']) continue;
                                if (isset($_GET['date_from']) && $_GET['date_from'] != '' && $booking['booking_date'] < $_GET['date_from']) continue;
                                if (isset($_GET['date_to']) && $_GET['date_to'] != '' && $booking['booking_date'] > $_GET['date_to']) continue;
                                $filtered_bookings[] = $booking;
                            }

                            if (count($filtered_bookings) > 0):
                                $no = 1;
                                foreach($filtered_bookings as $booking):
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <strong><?= date('d/m/Y', strtotime($booking['booking_date'])) ?></strong><br>
                                        <small class="text-muted"><?= date('H:i', strtotime($booking['booking_time'])) ?> น.</small>
                                    </td>
                                    <td>
                                        <small><?= nl2br(htmlspecialchars($booking['pickup_address'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($booking['total_weight']): ?>
                                            <span class="text-success fw-bold"><?= number_format($booking['total_weight'], 2) ?> kg</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($booking['estimated_price']): ?>
                                            <span class="text-success fw-bold"><?= number_format($booking['estimated_price'], 2) ?> ฿</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $booking['status'];
                                        $config = $status_config[$status] ?? $status_config['cancelled'];
                                        ?>
                                        <span class="badge <?= $config['class'] ?>"><?= $config['text'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="booking_detail.php?id=<?= $booking['booking_id'] ?>" class="btn btn-info btn-sm">ดู</a>
                                        <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $booking['booking_id'] ?>">ยกเลิก</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <div class="py-4">
                                            <h5>ยังไม่มีการจอง</h5>
                                            <p class="mb-3">เริ่มต้นรีไซเคิลกับเราวันนี้!</p>
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newBookingModal">
                                                สร้างการจองแรกของคุณ
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cancel Modals -->
        <?php foreach($filtered_bookings as $booking): ?>
            <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
            <div class="modal fade" id="cancelModal<?php echo $booking['booking_id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content border-danger">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">ยืนยันการยกเลิก</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <strong>คุณกำลังจะยกเลิกการจอง #<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                            </div>
                            <p><strong>วันที่:</strong> <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></p>
                            <p><strong>เวลา:</strong> <?php echo date('H:i', strtotime($booking['booking_time'])); ?> น.</p>
                            <div class="alert alert-info">
                                <small>การยกเลิกนี้ไม่สามารถย้อนกลับได้</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <form method="POST" action="sql/booking_cancel.php" class="w-100">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                    <button type="submit" class="btn btn-danger">ยืนยันยกเลิก</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Modal: New Booking -->
    <div class="modal fade" id="newBookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">จองรับขยะใหม่</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="sql/booking_create.php">
                    <div class="modal-body">
                        <!-- Date & Time -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">วันที่รับ <span class="text-danger">*</span></label>
                                <input type="date" name="booking_date" class="form-control" required
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                <small class="text-muted">จองล่วงหน้าอย่างน้อย 1 วัน</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เวลารับ <span class="text-danger">*</span></label>
                                <select name="booking_time" class="form-select" required>
                                    <option value="">เลือกเวลา</option>
                                    <option value="09:00:00">09:00 - 10:00 น.</option>
                                    <option value="10:00:00">10:00 - 11:00 น.</option>
                                    <option value="11:00:00">11:00 - 12:00 น.</option>
                                    <option value="13:00:00">13:00 - 14:00 น.</option>
                                    <option value="14:00:00">14:00 - 15:00 น.</option>
                                    <option value="15:00:00">15:00 - 16:00 น.</option>
                                    <option value="16:00:00">16:00 - 17:00 น.</option>
                                </select>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="mb-3">
                            <label class="form-label">ที่อยู่รับขยะ <span class="text-danger">*</span></label>
                            <textarea name="pickup_address" class="form-control" rows="3" required
                                      placeholder="กรอกที่อยู่สำหรับรับขยะ..."><?php echo htmlspecialchars($user['address']); ?></textarea>
                            <small class="text-muted">ใช้ที่อยู่จากโปรไฟล์ หรือกรอกที่อยู่อื่น</small>
                        </div>

                        <!-- Recycle Types Selection -->
                        <div class="mb-3">
                            <label class="form-label">เลือกประเภทขยะและระบุน้ำหนัก <span class="text-danger">*</span></label>
                            <p class="text-muted small">กรุณาเลือกอย่างน้อย 1 รายการ และระบุน้ำหนักโดยประมาณ</p>

                            <?php foreach ($types_by_category as $category => $types): ?>
                                <div class="mb-3">
                                    <h6 class="text-uppercase text-muted mb-2">
                                        <?= $category_names[$category] ?? $category ?>
                                    </h6>
                                    <?php foreach ($types as $type): ?>
                                        <div class="recycle-type-card card border-2 p-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="selected_types[]"
                                                       value="<?php echo $type['type_id']; ?>"
                                                       id="type_<?php echo $type['type_id']; ?>">
                                                <label class="form-check-label w-100" for="type_<?php echo $type['type_id']; ?>">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($type['type_name']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($type['description']); ?></small>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge bg-success"><?php echo number_format($type['price_per_kg'], 2); ?> ฿/kg</span>
                                                            <br>
                                                            <small class="text-muted">CO2: <?php echo $type['co2_reduction']; ?> kg</small>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                            <!-- Weight Input -->
                                            <div class="weight-input-wrapper">
                                                <label class="form-label small">น้ำหนักโดยประมาณ (kg)</label>
                                                <input type="number"
                                                       name="weights[<?php echo $type['type_id']; ?>]"
                                                       class="form-control form-control-sm"
                                                       placeholder="ระบุน้ำหนัก..."
                                                       step="0.01"
                                                       min="0.01">
                                                <small class="text-muted">ราคา: <?php echo number_format($type['price_per_kg'], 2); ?> บาท/กิโลกรัม</small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label class="form-label">หมายเหตุเพิ่มเติม</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="ระบุรายละเอียดเพิ่มเติม เช่น จุดรับที่ง่ายต่อการเข้าถึง..."></textarea>
                        </div>

                        <div class="alert alert-info">
                            <strong>หมายเหตุ:</strong>
                            <ul class="mb-0 mt-2">
                                <li>ราคาและน้ำหนักที่แสดงเป็นเพียงประมาณการเบื้องต้น</li>
                                <li>ราคาจริงจะคำนวณจากน้ำหนักที่ชั่งจริง ณ วันรับของ</li>
                                <li>เจ้าหน้าที่จะติดต่อกลับเพื่อยืนยันการนัดหมาย</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success">ยืนยันการจอง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
