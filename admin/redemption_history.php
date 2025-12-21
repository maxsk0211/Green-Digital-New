<?php
require_once '../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// ดึงข้อมูล Admin
$admin_sql = "SELECT * FROM admins WHERE admin_id = '$admin_id'";
$admin_result = mysqli_query($conn, $admin_sql);
$admin = mysqli_fetch_assoc($admin_result);

// ดึงประวัติการแลกทั้งหมด
$redemptions_sql = "SELECT rr.*, r.reward_name, r.category,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name, u.phone as user_phone,
                    a.full_name as admin_name
                    FROM reward_redemptions rr
                    JOIN rewards r ON rr.reward_id = r.reward_id
                    JOIN users u ON rr.user_id = u.user_id
                    LEFT JOIN admins a ON rr.redeemed_by = a.admin_id
                    
                    ORDER BY rr.redemption_date DESC";
$redemptions = mysqli_query($conn, $redemptions_sql);


?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการแลกของรางวัล - Green Digital Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>📋 ประวัติการแลกของรางวัล</h3>
            <a href="reward_redeem_for_user.php" class="btn btn-success">🎁 แลกของให้ลูกค้า</a>
        </div>

 

        <!-- ตารางข้อมูล -->
        <div class="redemption-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>รหัส</th>
                            <th>วันที่/เวลา</th>
                            <th>ลูกค้า</th>
                            <th>ของรางวัล</th>
                            <th>จำนวน</th>
                            <th>แต้มที่ใช้</th>
                            <th>วิธีรับ</th>
                            <th>พนักงาน</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($redemptions) > 0): ?>
                            <?php while($rd = mysqli_fetch_assoc($redemptions)): ?>
                                <tr>
                                    <td>#<?= $rd['redemption_id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($rd['redemption_date'])) ?></td>
                                    <td>
                                        <?= htmlspecialchars($rd['user_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($rd['user_phone']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($rd['reward_name']) ?></td>
                                    <td><?= $rd['quantity'] ?> ชิ้น</td>
                                    <td><span class="badge bg-warning text-dark"><?= number_format($rd['total_points']) ?></span></td>
                                    <td>
                                        <?php if($rd['delivery_method'] == 'delivery'): ?>
                                            🚚 จัดส่ง
                                        <?php else: ?>
                                            🏪 รับเอง
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($rd['admin_name']): ?>
                                            <?= htmlspecialchars($rd['admin_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($rd['status'] == 'completed'): ?>
                                            <span class="badge bg-success">เสร็จสิ้น</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">ยกเลิก</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <p class="text-muted mb-0">ไม่พบข้อมูล</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3 text-muted">
            <small>รายการทั้งหมด: <?= mysqli_num_rows($redemptions) ?> รายการ</small>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
