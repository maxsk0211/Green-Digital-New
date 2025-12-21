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

// กรองตามหมวดหมู่
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$where_clause = "status = 'active'";
if ($category_filter != 'all') {
    $where_clause .= " AND category = '$category_filter'";
}

// ดึงของรางวัลทั้งหมด
$rewards_sql = "SELECT * FROM rewards WHERE $where_clause ORDER BY points_required ASC";
$rewards = mysqli_query($conn, $rewards_sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ของรางวัล - Green Digital</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <!-- Points Hero -->
        <div class="card border-0 shadow-lg mb-4 bg-primary bg-gradient text-white">
            <div class="card-body text-center p-5">
                <h4 class="mb-3 opacity-75">⭐ แต้มสะสมของคุณ</h4>
                <h1 class="display-1 fw-bold mb-3"><?= number_format($current_points) ?></h1>
                <h4 class="mb-4 opacity-75">แต้ม</h4>
                <p class="mb-4 fs-5">ดูรายการของรางวัลที่สามารถแลกได้</p>
                <a href="my_redemptions.php" class="btn btn-light btn-lg shadow">
                    📋 ดูประวัติการแลกของฉัน
                </a>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="card border-0 shadow-sm mb-4 border-start border-danger border-5 bg-light">
            <div class="card-body p-4">
                <h5 class="mb-3 text-danger">💬 วิธีการแลกของรางวัล</h5>
                <p class="mb-0 text-dark">
                    กรุณาติดต่อเจ้าหน้าที่เพื่อแลกของรางวัล โดยแจ้งชื่อและเบอร์โทรศัพท์ของคุณ<br>
                    เจ้าหน้าที่จะช่วยตรวจสอบแต้มและดำเนินการแลกของให้คุณ
                </p>
            </div>
        </div>

        <!-- Category Filter -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <h6 class="mb-0 me-3">🔍 กรองตามหมวดหมู่:</h6>
                    <div class="btn-group shadow-sm" role="group">
                        <a href="rewards.php?category=all" class="btn <?= $category_filter == 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">ทั้งหมด</a>
                        <a href="rewards.php?category=food" class="btn <?= $category_filter == 'food' ? 'btn-primary' : 'btn-outline-primary' ?>">🍱 อาหาร</a>
                        <a href="rewards.php?category=product" class="btn <?= $category_filter == 'product' ? 'btn-primary' : 'btn-outline-primary' ?>">🎁 สินค้า</a>
                        <a href="rewards.php?category=voucher" class="btn <?= $category_filter == 'voucher' ? 'btn-primary' : 'btn-outline-primary' ?>">🎟️ คูปอง</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rewards Grid (Catalog View Only) -->
        <div class="row">
            <?php if(mysqli_num_rows($rewards) > 0): ?>
                <?php while($reward = mysqli_fetch_assoc($rewards)): ?>
                    <?php
                    $can_afford = $current_points >= $reward['points_required'];
                    $in_stock = $reward['stock_quantity'] == 0 || $reward['stock_quantity'] > 0;
                    $category_icon = [
                        'food' => '🍱',
                        'product' => '🎁',
                        'voucher' => '🎟️',
                        'discount' => '💰'
                    ];
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="position-relative">
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <span style="font-size: 5rem;"><?= $category_icon[$reward['category']] ?? '🎁' ?></span>
                                </div>
                                <span class="position-absolute top-0 end-0 m-3 badge bg-primary bg-gradient shadow fs-6 px-3 py-2">
                                    <?= number_format($reward['points_required']) ?> แต้ม
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold mb-2"><?= htmlspecialchars($reward['reward_name']) ?></h5>
                                <p class="card-text text-muted mb-3 flex-grow-1"><?= htmlspecialchars($reward['description']) ?></p>

                                <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                                    <div>
                                        <?php if($reward['stock_quantity'] == 0): ?>
                                            <span class="badge bg-success fs-6 px-3 py-2">✅ มีให้บริการ</span>
                                        <?php elseif($reward['stock_quantity'] > 0): ?>
                                            <span class="badge bg-success fs-6 px-3 py-2">📦 เหลือ <?= $reward['stock_quantity'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger fs-6 px-3 py-2">❌ หมดแล้ว</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if($can_afford): ?>
                                            <span class="badge bg-info fs-6 px-3 py-2">💰 แต้มพอ</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary fs-6 px-3 py-2">⚠️ แต้มไม่พอ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card bg-light border-0">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted fw-bold">💬 ติดต่อเจ้าหน้าที่เพื่อแลก</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-center p-5">
                        <div class="card-body">
                            <div class="display-1 mb-4">🎁</div>
                            <h4 class="mb-3">ยังไม่มีของรางวัลในหมวดหมู่นี้</h4>
                            <p class="text-muted">ลองเลือกหมวดหมู่อื่นเพื่อดูของรางวัลที่มีให้แลก</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
