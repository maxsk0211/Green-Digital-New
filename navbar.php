<!-- Navbar -->
<?php
// กำหนดค่าเริ่มต้นถ้ายังไม่มี
if (!isset($base_url)) {
    $base_url = '';
}
if (!isset($current_page)) {
    $current_page = '';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $base_url; ?>index.php">🌱 Green Digital</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>index.php">หน้าแรก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'articles') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>articles.php">บทความ</a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'bookings') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>bookings.php">จองรับขยะ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'transactions') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>transactions.php">ธุรกรรม</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'points') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>points.php">แต้มสะสม</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'rewards') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>rewards.php">ของรางวัล</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'profile') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>profile.php">โปรไฟล์</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>logout.php">ออกจากระบบ</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'login') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>user_login.php">เข้าสู่ระบบ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-success px-3 ms-2" href="<?php echo $base_url; ?>user_register.php">สมัครสมาชิก</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
