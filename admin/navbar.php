<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">🌿 Green Digital</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">📊 Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="bookings.php">📅 จัดการการจอง</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">👥 จัดการสมาชิก</a>
                </li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admins.php">👨‍💼 จัดการผู้ดูแล</a>
                </li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        ♻️ จัดการข้อมูล
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="recycle_types.php">ประเภทขยะ</a></li>
                        <li><a class="dropdown-item" href="prices.php">จัดการราคา</a></li>
                        <li><a class="dropdown-item" href="articles.php">บทความ</a></li>
                        <li><a class="dropdown-item" href="article_comments.php">💬 จัดการความคิดเห็น</a></li>
                        <li><a class="dropdown-item" href="rewards.php">🎁 ของรางวัล</a></li>
                        <li><a class="dropdown-item" href="promotions.php">โปรโมชั่น</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarRewardDropdown" role="button" data-bs-toggle="dropdown">
                        🎁 แลกของรางวัล
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="reward_redeem_for_user.php">แลกของให้ลูกค้า</a></li>
                        <li><a class="dropdown-item" href="redemption_history.php">ประวัติการแลก</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="transactions.php">💳 ธุรกรรม</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">📊 รายงาน</a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <!-- Notifications -->
                <?php
                // นับจำนวนการแจ้งเตือนทั้งหมด
                $notif_count_sql = "SELECT COUNT(*) as notif_count FROM notifications WHERE user_id IS NULL";
                $notif_count_result = mysqli_query($conn, $notif_count_sql);
                $notif_count = 0;
                if ($notif_count_result) {
                    $notif_count = mysqli_fetch_assoc($notif_count_result)['notif_count'];
                }

                // ดึงการแจ้งเตือน 5 รายการล่าสุด
                $notifications_sql = "SELECT * FROM notifications WHERE user_id IS NULL ORDER BY created_at DESC LIMIT 5";
                $notifications_result = mysqli_query($conn, $notifications_sql);
                ?>
                <div class="dropdown me-3">
                    <a class="nav-link text-white position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                        การแจ้งเตือน
                        <?php if($notif_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $notif_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end overflow-auto">
                        <li><h6 class="dropdown-header">🔔 การแจ้งเตือน</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if(mysqli_num_rows($notifications_result) > 0): ?>
                            <?php while($notif = mysqli_fetch_assoc($notifications_result)):?>
                            <li>
                                <div class="dropdown-item text-wrap">
                                    <div class="d-flex align-items-start">
                                        
                                        <div class="flex-grow-1">
                                            <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                                            <p class="mb-1 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endwhile; ?>
                            <li>
                                <a class="dropdown-item text-center text-primary" href="notifications.php">
                                    <strong>ดูทั้งหมด →</strong>
                                </a>
                            </li>
                        <?php else: ?>
                            <li><span class="dropdown-item text-muted text-center">ไม่มีการแจ้งเตือน</span></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <span class="text-white me-3">สวัสดี, <?php echo $_SESSION['full_name']; ?></span>
                <a href="logout.php" class="btn btn-light btn-sm">ออกจากระบบ</a>
            </div>
        </div>
    </div>
</nav>
