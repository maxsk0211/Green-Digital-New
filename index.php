<?php
session_start();
require_once 'config.php';

// ตั้งค่าสำหรับ navbar
$base_url = '';
$current_page = 'index';

// ดึงข้อมูลสถิติ
$total_users_sql = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$total_users_result = mysqli_query($conn, $total_users_sql);
$total_users = $total_users_result ? mysqli_fetch_assoc($total_users_result)['total'] : 0;

$total_bookings_sql = "SELECT COUNT(*) as total FROM bookings WHERE status = 'completed'";
$total_bookings_result = mysqli_query($conn, $total_bookings_sql);
$total_bookings = $total_bookings_result ? mysqli_fetch_assoc($total_bookings_result)['total'] : 0;

$total_co2_sql = "SELECT COALESCE(SUM(co2_reduced), 0) as total FROM carbon_footprint";
$total_co2_result = mysqli_query($conn, $total_co2_sql);
$total_co2 = $total_co2_result ? mysqli_fetch_assoc($total_co2_result)['total'] : 0;

// ดึงประเภทขยะที่รับซื้อ
$recycle_types_sql = "SELECT rt.*, p.price_per_kg
                      FROM recycle_types rt
                      LEFT JOIN prices p ON rt.type_id = p.type_id
                      WHERE rt.status = 'active' AND p.is_current = 1
                      ORDER BY rt.category";
$recycle_types = mysqli_query($conn, $recycle_types_sql);

// ดึงรีวิวลูกค้า
$reviews_sql = "SELECT r.*, u.first_name, u.last_name
                FROM reviews r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.status = 'approved' AND r.is_featured = 1
                ORDER BY r.created_at DESC
                LIMIT 3";
$reviews = mysqli_query($conn, $reviews_sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green Digital - แพลตฟอร์มรับซื้อขยะรีไซเคิลถึงที่</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="bg-primary text-white text-center py-5" id="home">
        <div class="container">
            <h1 class="display-1 fw-bold mb-4">♻️ รับซื้อขยะรีไซเคิลถึงที่</h1>
            <p class="lead mb-4">สะดวก รวดเร็ว ราคายุติธรรม พร้อมช่วยโลกไปด้วยกัน</p>
            <p class="mb-4">แค่จองผ่านแพลตฟอร์ม เราไปรับถึงบ้าน ชั่งหน้างาน โอนเงินทันที!</p>
            <div>
                <a href="user_register.php" class="btn btn-success btn-lg me-3">เริ่มต้นใช้งาน</a>
                <a href="#how-it-works" class="btn btn-outline-light btn-lg">เรียนรู้เพิ่มเติม</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">ทำไมต้อง Green Digital?</h2>
                <p class="lead">คุณสมบัติเด่นที่ทำให้เราแตกต่าง</p>
            </div>

            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-1 mb-3">🚚</div>
                            <h4>รับซื้อถึงที่</h4>
                            <p>ไม่ต้องเสียเวลาขนของไปขายเอง เราไปรับถึงบ้านคุณ ภายใน 24-48 ชั่วโมง</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-1 mb-3">💰</div>
                            <h4>ราคายุติธรรม</h4>
                            <p>ราคาโปร่งใส อัพเดทตามตลาดแบบเรียลไทม์ ชั่งหน้างาน โอนเงินทันที</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-1 mb-3">🌱</div>
                            <h4>ช่วยสิ่งแวดล้อม</h4>
                            <p>ติดตามปริมาณ CO2 ที่คุณช่วยลดได้ พร้อม Dashboard แสดงผลกระทบเชิงบวก</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-1 mb-3">⭐</div>
                            <h4>สะสมแต้ม</h4>
                            <p>Gamification สนุก สะสมแต้มทุกการขาย รับสิทธิพิเศษ ระดับ Bronze, Silver, Gold</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5" id="how-it-works">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">วิธีใช้งาน</h2>
                <p class="lead">ขั้นตอนง่ายๆ เพียง 4 ขั้นตอน</p>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <span class="fs-2 fw-bold">1</span>
                        </div>
                        <h4>สมัครสมาชิก</h4>
                        <p>ลงทะเบียนฟรี ใช้เวลาไม่ถึง 2 นาที</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-center">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <span class="fs-2 fw-bold">2</span>
                        </div>
                        <h4>จองรับซื้อ</h4>
                        <p>เลือกวันเวลา บอกประเภทและน้ำหนักโดยประมาณ</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-center">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <span class="fs-2 fw-bold">3</span>
                        </div>
                        <h4>เราไปรับถึงบ้าน</h4>
                        <p>ชั่งน้ำหนักหน้างาน ตรวจสอบคุณภาพ</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-center">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <span class="fs-2 fw-bold">4</span>
                        </div>
                        <h4>รับเงินทันที</h4>
                        <p>โอนเงินเข้าบัญชี รับแต้มสะสม ช่วยโลกไปด้วยกัน!</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recycle Types Section -->
    <section class="py-5 bg-light" id="recycle-types">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">ประเภทขยะที่รับซื้อ</h2>
                <p class="lead">ราคาอัพเดทตามตลาดแบบเรียลไทม์</p>
            </div>

            <div class="row">
                <?php if($recycle_types && mysqli_num_rows($recycle_types) > 0): ?>
                    <?php while($type = mysqli_fetch_assoc($recycle_types)): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="display-4 mb-3">
                                        <?php
                                        // กำหนดไอคอนตามประเภท
                                        $icon = '♻️';
                                        if($type['category'] == 'plastic') $icon = '🍾';
                                        elseif($type['category'] == 'paper') $icon = '📄';
                                        elseif($type['category'] == 'metal') $icon = '🥫';
                                        elseif($type['category'] == 'glass') $icon = '🍶';
                                        echo $icon;
                                        ?>
                                    </div>
                                    <h5><?php echo htmlspecialchars($type['type_name']); ?></h5>
                                    <p class="text-muted small"><?php echo htmlspecialchars($type['description']); ?></p>
                                    <div class="badge bg-success p-2 mb-2">
                                        <?php echo number_format($type['price_per_kg'], 2); ?> ฿/kg
                                    </div>
                                    <p class="text-success small">
                                        🌱 ลด CO2: <?php echo number_format($type['co2_reduction'], 2); ?> kg
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">ยังไม่มีข้อมูลประเภทขยะ</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5 bg-success text-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">ผลกระทบที่เราสร้างร่วมกัน</h2>
                <p class="lead">ตัวเลขที่พิสูจน์ว่าเราช่วยโลกได้จริง</p>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="display-1 fw-bold"><?php echo number_format($total_users); ?>+</div>
                        <div class="fs-4">สมาชิกที่ไว้วางใจ</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="display-1 fw-bold"><?php echo number_format($total_bookings); ?>+</div>
                        <div class="fs-4">การรับซื้อที่สำเร็จ</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="display-1 fw-bold"><?php echo number_format($total_co2, 2); ?></div>
                        <div class="fs-4">kg CO2 ที่ช่วยลดได้</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews Section -->
    <?php if($reviews && mysqli_num_rows($reviews) > 0): ?>
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">รีวิวจากลูกค้า</h2>
                <p class="lead">ความพึงพอใจจากผู้ใช้งานจริง</p>
            </div>

            <div class="row">
                <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-warning mb-3">
                                    <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                        ⭐
                                    <?php endfor; ?>
                                </div>
                                <p><?php echo htmlspecialchars($review['comment']); ?></p>
                                <div class="fw-bold text-success">
                                    - <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white text-center">
        <div class="container">
            <h2 class="display-4 fw-bold mb-4">พร้อมเริ่มต้นช่วยโลกแล้วหรือยัง?</h2>
            <p class="lead mb-4">ขายขยะรีไซเคิล รับเงิน และช่วยลด CO2 ไปพร้อมๆ กัน</p>
            <a href="user_register.php" class="btn btn-success btn-lg px-5">สมัครสมาชิกฟรี</a>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
