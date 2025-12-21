<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// 1. สถิติผู้ใช้งาน
$users_sql = "SELECT COUNT(*) as total FROM users";
$users_result = mysqli_query($conn, $users_sql);
$total_users = 0;
if ($users_result) {
    $total_users = mysqli_fetch_assoc($users_result)['total'];
}

// 2. สถิติการจอง
$bookings_sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled
                 FROM bookings";
$bookings_result = mysqli_query($conn, $bookings_sql);
$bookings_stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
if ($bookings_result) {
    $bookings_stats = mysqli_fetch_assoc($bookings_result);
}

// 3. สถิติธุรกรรม
$trans_sql = "SELECT
                COUNT(*) as total,
                SUM(total_weight) as total_weight,
                SUM(total_amount) as total_amount,
                SUM(CASE WHEN payment_status='paid' THEN total_amount ELSE 0 END) as paid_amount
              FROM transactions";
$trans_result = mysqli_query($conn, $trans_sql);
$trans_stats = ['total' => 0, 'total_weight' => 0, 'total_amount' => 0, 'paid_amount' => 0];
if ($trans_result) {
    $trans_stats = mysqli_fetch_assoc($trans_result);
}

// 4. สถิติ CO2 และสิ่งแวดล้อม
$carbon_sql = "SELECT
                COUNT(*) as total,
                SUM(co2_reduced) as total_co2,
                SUM(trees_equivalent) as total_trees,
                SUM(energy_saved) as total_energy
               FROM carbon_footprint";
$carbon_result = mysqli_query($conn, $carbon_sql);
$carbon_stats = ['total' => 0, 'total_co2' => 0, 'total_trees' => 0, 'total_energy' => 0];
if ($carbon_result) {
    $carbon_stats = mysqli_fetch_assoc($carbon_result);
}

// 5. สถิติแต้มสะสม
$points_sql = "SELECT
                SUM(CASE WHEN transaction_type='earn' THEN points ELSE 0 END) as earned,
                SUM(CASE WHEN transaction_type='redeem' THEN points ELSE 0 END) as redeemed
               FROM point_transactions";
$points_result = mysqli_query($conn, $points_sql);
$points_stats = ['earned' => 0, 'redeemed' => 0];
if ($points_result) {
    $points_stats = mysqli_fetch_assoc($points_result);
}

// 6. ประเภทขยะที่นิยม (Top 5)
$top_types_sql = "SELECT rt.type_name, SUM(bi.quantity) as total_qty
                  FROM booking_items bi
                  JOIN recycle_types rt ON bi.type_id = rt.type_id
                  GROUP BY bi.type_id
                  ORDER BY total_qty DESC
                  LIMIT 5";
$top_types_result = mysqli_query($conn, $top_types_sql);

// 7. สถิติบทความ
$articles_sql = "SELECT COUNT(*) as total FROM articles";
$articles_result = mysqli_query($conn, $articles_sql);
$total_articles = 0;
if ($articles_result) {
    $total_articles = mysqli_fetch_assoc($articles_result)['total'];
}

// 8. สถิติของรางวัล
$rewards_sql = "SELECT COUNT(*) as total FROM rewards";
$rewards_result = mysqli_query($conn, $rewards_sql);
$total_rewards = 0;
if ($rewards_result) {
    $total_rewards = mysqli_fetch_assoc($rewards_result)['total'];
}

$redemptions_sql = "SELECT COUNT(*) as total FROM redemption_history";
$redemptions_result = mysqli_query($conn, $redemptions_sql);
$total_redemptions = 0;
if ($redemptions_result) {
    $total_redemptions = mysqli_fetch_assoc($redemptions_result)['total'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสรุป - Green Digital Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h3 class="mb-4">📊 รายงานสรุปภาพรวมระบบ</h3>

        <!-- สถิติผู้ใช้งาน -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">👥 สถิติผู้ใช้งาน</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h4><?php echo number_format($total_users); ?> คน</h4>
                        <p class="text-muted mb-0">จำนวนสมาชิกทั้งหมดในระบบ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- สถิติการจอง -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">📅 สถิติการจอง</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h4><?php echo number_format($bookings_stats['total']); ?></h4>
                        <p class="text-muted mb-0">การจองทั้งหมด</p>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-warning"><?php echo number_format($bookings_stats['pending']); ?></h4>
                        <p class="text-muted mb-0">รอดำเนินการ</p>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-primary"><?php echo number_format($bookings_stats['confirmed']); ?></h4>
                        <p class="text-muted mb-0">ยืนยันแล้ว</p>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-success"><?php echo number_format($bookings_stats['completed']); ?></h4>
                        <p class="text-muted mb-0">เสร็จสิ้น</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- สถิติธุรกรรมและรายได้ -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">💰 สถิติธุรกรรมและรายได้</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h4><?php echo number_format($trans_stats['total']); ?></h4>
                        <p class="text-muted mb-0">ธุรกรรมทั้งหมด</p>
                    </div>
                    <div class="col-md-3">
                        <h4><?php echo number_format($trans_stats['total_weight'], 2); ?> kg</h4>
                        <p class="text-muted mb-0">น้ำหนักรวม</p>
                    </div>
                    <div class="col-md-3">
                        <h4>฿<?php echo number_format($trans_stats['total_amount'], 2); ?></h4>
                        <p class="text-muted mb-0">มูลค่ารวม</p>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-success">฿<?php echo number_format($trans_stats['paid_amount'], 2); ?></h4>
                        <p class="text-muted mb-0">ชำระเงินแล้ว</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- สถิติสิ่งแวดล้อม -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">🌱 สถิติการช่วยเหลือสิ่งแวดล้อม</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h4><?php echo number_format($carbon_stats['total_co2'], 2); ?> kg</h4>
                        <p class="text-muted mb-0">CO2 ที่ลดได้</p>
                    </div>
                    <div class="col-md-4">
                        <h4><?php echo number_format($carbon_stats['total_trees'], 2); ?> ต้น</h4>
                        <p class="text-muted mb-0">เทียบเท่าต้นไม้</p>
                    </div>
                    <div class="col-md-4">
                        <h4><?php echo number_format($carbon_stats['total_energy'], 2); ?> kWh</h4>
                        <p class="text-muted mb-0">พลังงานที่ประหยัด</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- สถิติแต้มสะสม -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">⭐ สถิติแต้มสะสม</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-6">
                        <h4 class="text-success"><?php echo number_format($points_stats['earned']); ?></h4>
                        <p class="text-muted mb-0">แต้มที่ได้รับทั้งหมด</p>
                    </div>
                    <div class="col-md-6">
                        <h4 class="text-danger"><?php echo number_format($points_stats['redeemed']); ?></h4>
                        <p class="text-muted mb-0">แต้มที่แลกไปแล้ว</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ประเภทขยะที่นิยม -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">♻️ ประเภทขยะที่นิยม (Top 5)</h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($top_types_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>อันดับ</th>
                                    <th>ประเภทขยะ</th>
                                    <th class="text-end">ปริมาณรวม (kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                while($row = mysqli_fetch_assoc($top_types_result)):
                                ?>
                                <tr>
                                    <td><?php echo $rank; ?></td>
                                    <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                                    <td class="text-end"><?php echo number_format($row['total_qty'], 2); ?></td>
                                </tr>
                                <?php
                                $rank++;
                                endwhile;
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">ยังไม่มีข้อมูล</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- สถิติเนื้อหาและของรางวัล -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">📚 สถิติเนื้อหาและของรางวัล</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h4><?php echo number_format($total_articles); ?></h4>
                        <p class="text-muted mb-0">บทความทั้งหมด</p>
                    </div>
                    <div class="col-md-4">
                        <h4><?php echo number_format($total_rewards); ?></h4>
                        <p class="text-muted mb-0">ของรางวัลทั้งหมด</p>
                    </div>
                    <div class="col-md-4">
                        <h4><?php echo number_format($total_redemptions); ?></h4>
                        <p class="text-muted mb-0">การแลกของรางวัล</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
