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

// ดึงประวัติการแลกของรางวัล
$redemptions_sql = "SELECT rr.*, r.reward_name, r.category,
                    a.full_name as admin_name
                    FROM reward_redemptions rr
                    JOIN rewards r ON rr.reward_id = r.reward_id
                    LEFT JOIN admins a ON rr.redeemed_by = a.admin_id
                    WHERE rr.user_id = $user_id
                    ORDER BY rr.redemption_date DESC";
$redemptions = mysqli_query($conn, $redemptions_sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการแลกของรางวัล - Green Digital</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <!-- Header -->
        <div class="card border-0 shadow mb-4 bg-primary bg-gradient text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-2 fw-bold">📋 ประวัติการแลกของรางวัล</h2>
                        <p class="mb-0 opacity-75">ดูประวัติการแลกของรางวัลทั้งหมดของคุณ</p>
                    </div>
                    <a href="rewards.php" class="btn btn-light btn-lg shadow">
                        🎁 ดูของรางวัล
                    </a>
                </div>
            </div>
        </div>

        <?php if(mysqli_num_rows($redemptions) > 0): ?>
            <?php while($rd = mysqli_fetch_assoc($redemptions)): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="fw-bold mb-3"><?= htmlspecialchars($rd['reward_name']) ?></h5>
                                <div class="text-muted">
                                    <div class="mb-1">📅 วันที่: <?= date('d/m/Y H:i น.', strtotime($rd['redemption_date'])) ?></div>
                                    <div class="mb-1">📦 จำนวน: <?= $rd['quantity'] ?> ชิ้น</div>
                                    <?php if($rd['delivery_method'] == 'delivery'): ?>
                                        <div class="mb-1">🚚 จัดส่ง: <?= htmlspecialchars($rd['delivery_address']) ?></div>
                                    <?php else: ?>
                                        <div class="mb-1">🏪 รับเอง</div>
                                    <?php endif; ?>
                                    <?php if($rd['admin_name']): ?>
                                        <div class="mb-1">👤 ดำเนินการโดย: <?= htmlspecialchars($rd['admin_name']) ?></div>
                                    <?php endif; ?>
                                    <?php if($rd['notes']): ?>
                                        <div class="mb-1">📝 หมายเหตุ: <?= htmlspecialchars($rd['notes']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="mb-3">
                                    <h3 class="text-danger fw-bold mb-0">-<?= number_format($rd['total_points']) ?></h3>
                                    <span class="text-muted">แต้ม</span>
                                </div>
                                <?php if($rd['status'] == 'completed'): ?>
                                    <span class="badge bg-success fs-6 px-3 py-2">✅ เสร็จสิ้น</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary fs-6 px-3 py-2"><?= $rd['status'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card border-0 shadow-sm text-center p-5">
                <div class="card-body">
                    <div class="display-1 mb-4">📋</div>
                    <h4 class="mb-3">ยังไม่มีประวัติการแลกของรางวัล</h4>
                    <p class="text-muted mb-4">เริ่มต้นแลกของรางวัลด้วยแต้มสะสมของคุณ</p>
                    <a href="rewards.php" class="btn btn-primary btn-lg shadow">
                        🎁 ดูของรางวัล
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
