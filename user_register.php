<?php
session_start();

// ตั้งค่าสำหรับ navbar
$base_url = '';
$current_page = 'register';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Green Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-4">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h2 class="text-success">🌱 สมัครสมาชิก</h2>
                            <p class="text-muted">Green Digital - แพลตฟอร์มรับซื้อขยะรีไซเคิลถึงที่</p>
                        </div>

                        <form action="sql/register_process.php" method="POST">
                            <div class="row">
                                <!-- Email -->
                                <div class="col-12 mb-3">
                                    <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                                </div>

                                <!-- Password -->
                                <div class="col-12 mb-3">
                                    <label class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน (อย่างน้อย 6 ตัวอักษร)" minlength="6" required>
                                </div>

                                <!-- Confirm Password -->
                                <div class="col-12 mb-3">
                                    <label class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="กรอกรหัสผ่านอีกครั้ง" minlength="6" required>
                                </div>

                                <!-- First Name -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ชื่อจริง <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" placeholder="ชื่อ" required>
                                </div>

                                <!-- Last Name -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" placeholder="นามสกุล" required>
                                </div>

                                <!-- Phone -->
                                <div class="col-12 mb-3">
                                    <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" class="form-control" placeholder="0812345678" pattern="[0-9]{10}" required>
                                    <small class="text-muted">ตัวอย่าง: 0812345678 (10 หลัก)</small>
                                </div>

                                <!-- Address -->
                                <div class="col-12 mb-3">
                                    <label class="form-label">ที่อยู่ <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control" rows="3" placeholder="บ้านเลขที่ หมู่บ้าน ซอย ถนน" required></textarea>
                                </div>

                                <!-- Province -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                                    <input type="text" name="province" class="form-control" placeholder="เช่น กรุงเทพมหานคร" required>
                                </div>

                                <!-- District -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">เขต/อำเภอ <span class="text-danger">*</span></label>
                                    <input type="text" name="district" class="form-control" placeholder="เช่น บางกะปิ" required>
                                </div>

                                <!-- Security Answer -->
                                <div class="col-12 mb-3">
                                    <label class="form-label">เพื่อนสนิทของคุณคือใคร? <span class="text-danger">*</span></label>
                                    <input type="text" name="security_answer" class="form-control" placeholder="ใช้สำหรับกู้คืนรหัสผ่าน" required>
                                    <small class="text-muted">⚠️ จำคำตอบให้ดี เพราะจะใช้ในการกู้คืนรหัสผ่าน (ตรงตัวพิมพ์เล็ก-ใหญ่)</small>
                                </div>

                                <!-- Submit Button -->
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        ✅ สมัครสมาชิก
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-2">มีบัญชีอยู่แล้ว? <a href="user_login.php" class="text-decoration-none">เข้าสู่ระบบ</a></p>
                            <a href="index.php" class="text-decoration-none">← กลับหน้าแรก</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- แสดง Alert ถ้ามี Session -->
    <?php if(isset($_SESSION['success'])): ?>
        <script>
            alert("<?php echo $_SESSION['success']; ?>");
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <script>
            alert("<?php echo $_SESSION['error']; ?>");
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Validate Password Match -->
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            var password = document.querySelector('input[name="password"]').value;
            var confirm = document.querySelector('input[name="confirm_password"]').value;

            if(password !== confirm) {
                e.preventDefault();
                alert('รหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
            }
        });
    </script>
</body>
</html>
