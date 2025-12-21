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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ - Green Digital</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        .badge-bronze { background: linear-gradient(135deg, #cd7f32 0%, #a0522d 100%); color: white; }
        .badge-silver { background: linear-gradient(135deg, #c0c0c0 0%, #808080 100%); color: white; }
        .badge-gold { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #333; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">👤 โปรไฟล์ของฉัน</h2>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php
                if($_GET['success'] == 'updated') echo 'อัปเดตข้อมูลสำเร็จ';
                elseif($_GET['success'] == 'password_changed') echo 'เปลี่ยนรหัสผ่านสำเร็จ';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php
                if($_GET['error'] == 'password_mismatch') echo 'รหัสผ่านไม่ตรงกัน';
                elseif($_GET['error'] == 'wrong_password') echo 'รหัสผ่านเดิมไม่ถูกต้อง';
                else echo 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Info -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center display-3" style="width: 100px; height: 100px;">
                                👤
                            </div>
                        </div>
                        <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                        <?php
                        $badge_class = 'badge-bronze';
                        if($user['user_level'] == 'Silver') $badge_class = 'badge-silver';
                        if($user['user_level'] == 'Gold') $badge_class = 'badge-gold';
                        ?>
                        <span class="badge <?php echo $badge_class; ?> mb-3"><?php echo $user['user_level']; ?></span>
                        <hr>
                        <div class="text-start">
                            <p class="mb-2"><strong>แต้มสะสม:</strong> <?php echo number_format($user['total_points']); ?> แต้ม</p>
                            <p class="mb-2"><strong>สถานะ:</strong>
                                <?php if($user['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Suspended</span>
                                <?php endif; ?>
                            </p>
                            <p class="mb-0"><strong>สมัครเมื่อ:</strong> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">✏️ แก้ไขข้อมูลส่วนตัว</h5>
                    </div>
                    <div class="card-body">
                        <form action="sql/profile_update.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">อีเมล</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    <small class="text-muted">อีเมลไม่สามารถแก้ไขได้</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">เบอร์โทร <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ที่อยู่</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">จังหวัด</label>
                                    <input type="text" name="province" class="form-control" value="<?php echo htmlspecialchars($user['province']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">เขต/อำเภอ</label>
                                    <input type="text" name="district" class="form-control" value="<?php echo htmlspecialchars($user['district']); ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">💾 บันทึกข้อมูล</button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">🔒 เปลี่ยนรหัสผ่าน</h5>
                    </div>
                    <div class="card-body">
                        <form action="sql/password_change.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">รหัสผ่านเดิม <span class="text-danger">*</span></label>
                                <input type="password" name="old_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required>
                                <small class="text-muted">ความยาวอย่างน้อย 6 ตัวอักษร</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ยืนยันรหัสผ่านใหม่ <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                            </div>

                            <button type="submit" class="btn btn-warning">🔐 เปลี่ยนรหัสผ่าน</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
