<?php
session_start();

// ตรวจสอบว่า verify แล้วหรือยัง
if (!isset($_SESSION['reset_verified']) || !isset($_SESSION['forgot_user_id'])) {
    header("Location: forgot_password.php");
    exit();
}

// ตั้งค่าสำหรับ navbar
$base_url = '';
$current_page = 'reset_password';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่ - Green Digital</title>
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
                                    <span class="display-4">✅</span>
                                </div>
                                <h2 class="fw-bold text-success mb-2">ยืนยันตัวตนสำเร็จ</h2>
                                <p class="text-muted">ตั้งรหัสผ่านใหม่</p>
                            </div>

                            <!-- User Info -->
                            <div class="card bg-success bg-opacity-10 border-0 mb-4">
                                <div class="card-body">
                                    <small class="text-muted d-block mb-1">บัญชี:</small>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['forgot_name'] ?? 'ผู้ใช้'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($_SESSION['forgot_email'] ?? ''); ?></small>
                                </div>
                            </div>

                            <!-- Error Alert -->
                            <?php if (isset($_GET['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                                    <?php
                                    if ($_GET['error'] == 'password_mismatch') {
                                        echo '❌ รหัสผ่านไม่ตรงกัน';
                                    } elseif ($_GET['error'] == 'password_short') {
                                        echo '❌ รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
                                    } else {
                                        echo '❌ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
                                    }
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Form -->
                            <form action="sql/reset_password_process.php" method="POST" id="resetForm">
                                <div class="mb-4">
                                    <label for="new_password" class="form-label fw-bold">รหัสผ่านใหม่</label>
                                    <input type="password" class="form-control form-control-lg shadow-sm" id="new_password" name="new_password" minlength="6" required autofocus>
                                    <small class="text-muted">อย่างน้อย 6 ตัวอักษร</small>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label fw-bold">ยืนยันรหัสผ่านใหม่</label>
                                    <input type="password" class="form-control form-control-lg shadow-sm" id="confirm_password" name="confirm_password" minlength="6" required>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100 shadow fw-bold mb-3">
                                    บันทึกรหัสผ่านใหม่ ✓
                                </button>
                            </form>

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
    <script>
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            var password = document.getElementById('new_password').value;
            var confirm = document.getElementById('confirm_password').value;

            if(password !== confirm) {
                e.preventDefault();
                alert('รหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
            }

            if(password.length < 6) {
                e.preventDefault();
                alert('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
            }
        });
    </script>
</body>
</html>
