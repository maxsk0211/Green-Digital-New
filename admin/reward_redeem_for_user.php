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

// ค้นหาลูกค้า
$user = null;
$user_points = 0;
$search_error = null;
$debug_query = null; // เพิ่มตัวแปร debug
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);

    // สร้างเงื่อนไขค้นหา
    $where_conditions = [];

    // ถ้าเป็นตัวเลข ให้ค้นหาจาก user_id ด้วย
    if (is_numeric($search)) {
        $where_conditions[] = "user_id = " . intval($search);
    }

    // ค้นหาจาก phone, first_name, last_name เสมอ
    $where_conditions[] = "phone LIKE '%$search%'";
    $where_conditions[] = "first_name LIKE '%$search%'";
    $where_conditions[] = "last_name LIKE '%$search%'";
    $where_conditions[] = "CONCAT(first_name, ' ', last_name) LIKE '%$search%'";

    $where_clause = implode(' OR ', $where_conditions);

    $user_sql = "SELECT * FROM users WHERE $where_clause LIMIT 1";
    $debug_query = $user_sql; // เก็บ query สำหรับ debug
    $user_result = mysqli_query($conn, $user_sql);

    if (!$user_result) {
        $search_error = "เกิดข้อผิดพลาดในการค้นหา: " . mysqli_error($conn);
    } elseif (mysqli_num_rows($user_result) > 0) {
        $user = mysqli_fetch_assoc($user_result);
        $user_points = $user['points'] ?? 0;
    }
}

// ดึงรายการของรางวัลที่พร้อมแลก
$rewards_sql = "SELECT * FROM rewards WHERE status = 'active' ORDER BY category, points_required";
$rewards = mysqli_query($conn, $rewards_sql);

// ตรวจสอบว่ามีตาราง rewards หรือไม่
if (!$rewards) {
    die("Error: ไม่พบตาราง rewards กรุณารันไฟล์ SQL: add_rewards_system.sql ก่อน<br>MySQL Error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แลกของรางวัลให้ลูกค้า - Green Digital Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <h3 class="mb-4">🎁 แลกของรางวัลให้ลูกค้า</h3>

        <!-- ส่วนค้นหาลูกค้า -->
        <div class="search-section">
            <h5 class="mb-3">🔍 ค้นหาลูกค้า</h5>
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control"
                               placeholder="ค้นหาด้วย รหัสลูกค้า, เบอร์โทร หรือ ชื่อ-นามสกุล"
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">🔍 ค้นหา</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($search_error): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($search_error) ?>
            </div>
        <?php endif; ?>

        <!-- Debug Info (แสดงเมื่อมีการค้นหาแต่ไม่พบผล) -->
        <?php if (isset($_GET['debug']) && $debug_query): ?>
            <div class="alert alert-info">
                <strong>🔍 Debug Query:</strong><br>
                <code><?= htmlspecialchars($debug_query) ?></code><br>
                <strong>ค้นหาคำว่า:</strong> "<?= htmlspecialchars($_GET['search']) ?>"<br>
                <strong>ผลลัพธ์:</strong> <?= $user ? 'พบข้อมูล' : 'ไม่พบข้อมูล' ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['search'])): ?>
            <?php if ($user): ?>
                <!-- ข้อมูลลูกค้า -->
                <div class="user-info-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                            <p class="mb-1">📱 เบอร์โทร: <?= htmlspecialchars($user['phone']) ?></p>
                            <p class="mb-1">🆔 รหัสลูกค้า: <?= $user['user_id'] ?></p>
                            <p class="mb-0">📧 อีเมล: <?= htmlspecialchars($user['email'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="points-display"><?= number_format($user_points) ?></div>
                            <p class="mb-0">แต้มคงเหลือ</p>
                        </div>
                    </div>
                </div>

                <!-- รายการของรางวัล -->
                <h5 class="mb-3">📦 เลือกของรางวัล</h5>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        ✅ แลกของรางวัลเรียบร้อยแล้ว!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        ❌ <?php
                        switch($_GET['error']) {
                            case 'insufficient_points':
                                echo 'แต้มไม่เพียงพอ';
                                break;
                            case 'out_of_stock':
                                echo 'สินค้าหมด';
                                break;
                            case 'invalid_quantity':
                                echo 'จำนวนไม่ถูกต้อง';
                                break;
                            default:
                                echo 'เกิดข้อผิดพลาด';
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php
                $category_names = [
                    'food' => '🍽️ อาหาร',
                    'product' => '📦 สินค้า',
                    'voucher' => '🎫 คูปอง',
                    'discount' => '💰 ส่วนลด'
                ];

                if (mysqli_num_rows($rewards) > 0):
                    while($reward = mysqli_fetch_assoc($rewards)):
                        $can_afford = $user_points >= $reward['points_required'];
                        $in_stock = $reward['stock_quantity'] == 0 || $reward['stock_quantity'] > 0;
                        $can_redeem = $can_afford && $in_stock;
                ?>
                    <div class="reward-card <?= $can_redeem ? '' : 'disabled' ?>">
                        <form method="POST" action="sql/reward_redeem_for_user_process.php" onsubmit="return <?= $can_redeem ? 'confirm(\'ยืนยันการแลกของรางวัลนี้?\')' : 'false' ?>">
                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                            <input type="hidden" name="reward_id" value="<?= $reward['reward_id'] ?>">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">

                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5><?= htmlspecialchars($reward['reward_name']) ?></h5>
                                    <span class="category-badge bg-info text-white">
                                        <?= $category_names[$reward['category']] ?? $reward['category'] ?>
                                    </span>
                                    <?php if ($reward['description']): ?>
                                        <p class="text-muted mb-1 mt-2"><?= htmlspecialchars($reward['description']) ?></p>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        📦 สต็อก: <?= $reward['stock_quantity'] == 0 ? 'ไม่จำกัด' : $reward['stock_quantity'] . ' ชิ้น' ?>
                                    </small>
                                </div>

                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label">จำนวน</label>
                                        <input type="number" name="quantity" class="form-control"
                                               value="1" min="1"
                                               max="<?= $reward['stock_quantity'] > 0 ? $reward['stock_quantity'] : 999 ?>"
                                               <?= !$can_redeem ? 'disabled' : '' ?> required>
                                    </div>
                                    <div>
                                        <label class="form-label">วิธีรับ</label>
                                        <select name="delivery_method" class="form-select" <?= !$can_redeem ? 'disabled' : '' ?>>
                                            <option value="pickup">มารับเอง</option>
                                            <option value="delivery">จัดส่ง</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3 text-end">
                                    <div class="mb-3">
                                        <span class="badge bg-warning text-dark" style="font-size: 1.2rem;">
                                            <?= number_format($reward['points_required']) ?> แต้ม
                                        </span>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100" <?= !$can_redeem ? 'disabled' : '' ?>>
                                        <?php if (!$can_afford): ?>
                                            ❌ แต้มไม่พอ
                                        <?php elseif (!$in_stock): ?>
                                            ❌ สินค้าหมด
                                        <?php else: ?>
                                            ✅ แลกให้ลูกค้า
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php
                    endwhile;
                else:
                ?>
                    <div class="alert alert-info">ไม่มีของรางวัลในขณะนี้</div>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ ไม่พบข้อมูลลูกค้า กรุณาตรวจสอบข้อมูลและค้นหาอีกครั้ง
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
