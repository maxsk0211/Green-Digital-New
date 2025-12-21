<?php
session_start();
require_once 'config.php';

// ตั้งค่าสำหรับ navbar
$base_url = '';
$current_page = 'forgot_password';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - Green Digital</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="bg-primary bg-gradient">
    <div class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-5">
                            <!-- Header -->
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <span class="display-4">🔐</span>
                                </div>
                                <h2 class="fw-bold text-success mb-2">ลืมรหัสผ่าน</h2>
                                <p class="text-muted">กู้คืนรหัสผ่านของคุณ</p>
                            </div>

                            <!-- Step Indicator -->
                            <div class="card bg-success bg-opacity-10 border-0 border-start border-success border-4 mb-4">
                                <div class="card-body">
                                    <strong class="text-success">
                                        <?php if (!isset($_SESSION['forgot_user_id'])): ?>
                                            ขั้นตอนที่ 1:
                                        <?php else: ?>
                                            ขั้นตอนที่ 2:
                                        <?php endif; ?>
                                    </strong>
                                    <?php if (!isset($_SESSION['forgot_user_id'])): ?>
                                        กรอกอีเมลหรือเบอร์โทรศัพท์
                                    <?php else: ?>
                                        ตอบคำถามความปลอดภัย
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Error Alert -->
                            <?php if (isset($_GET['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                                    <?php
                                    if ($_GET['error'] == 'not_found') {
                                        echo '❌ ไม่พบผู้ใช้นี้ในระบบ';
                                    } elseif ($_GET['error'] == 'wrong_answer') {
                                        echo '❌ คำตอบไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
                                    } else {
                                        echo '❌ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
                                    }
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (!isset($_SESSION['forgot_user_id'])): ?>
                                <!-- Step 1: กรอกอีเมลหรือเบอร์โทร -->
                                <form action="sql/forgot_password_step1.php" method="POST">
                                    <div class="mb-4">
                                        <label for="username" class="form-label fw-bold">อีเมล หรือ เบอร์โทรศัพท์</label>
                                        <input type="text" class="form-control form-control-lg shadow-sm" id="username" name="username" required autofocus>
                                        <small class="text-muted">กรอกอีเมลหรือเบอร์โทรที่ใช้สมัครสมาชิก</small>
                                    </div>

                                    <button type="submit" class="btn btn-success btn-lg w-100 shadow fw-bold mb-3">
                                        ถัดไป →
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Step 2: ตอบคำถามความปลอดภัย -->
                                <form action="sql/forgot_password_step2.php" method="POST">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">คำถาม:</label>
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                เพื่อนสนิทของคุณคือใคร?
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="security_answer" class="form-label fw-bold">คำตอบของคุณ</label>
                                        <input type="text" class="form-control form-control-lg shadow-sm" id="security_answer" name="security_answer" required autofocus>
                                        <small class="text-muted">⚠️ ตรงตัวพิมพ์เล็ก-ใหญ่</small>
                                    </div>

                                    <button type="submit" class="btn btn-success btn-lg w-100 shadow fw-bold mb-3">
                                        ตรวจสอบ ✓
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- Back Link -->
                            <div class="text-center">
                                <a href="user_login.php" class="text-decoration-none">
                                    ← กลับไปหน้าเข้าสู่ระบบ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
