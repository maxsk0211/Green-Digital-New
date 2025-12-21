<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ Admin - Green Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h1 class="h2 text-success">🌿 Green Digital</h1>
                            <p class="text-muted">ระบบผู้ดูแล</p>
                            <?php
                            // echo password_hash(password: "admin", algo: PASSWORD_DEFAULT);
                            ?>      
                        </div>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <strong>ผิดพลาด!</strong>
                                <?php
                                if ($_GET['error'] == 'suspended') {
                                    echo 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
                                } else {
                                    echo 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="sql/login.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                                <input type="text" class="form-control form-control-lg" id="username" name="username" required autofocus>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">รหัสผ่าน</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100">เข้าสู่ระบบ</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="index.php" class="text-decoration-none">← กลับหน้าหลัก</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
