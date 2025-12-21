<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// รับค่าการค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query สมาชิก
$sql = "SELECT * FROM users WHERE 1=1";

if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (first_name LIKE '%$search_escaped%' OR last_name LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%' OR phone LIKE '%$search_escaped%')";
}

$sql .= " ORDER BY created_at DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสมาชิก - Green Digital</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        .badge-bronze { background: linear-gradient(135deg, #cd7f32 0%, #a0522d 100%); color: white; }
        .badge-silver { background: linear-gradient(135deg, #c0c0c0 0%, #808080 100%); color: white; }
        .badge-gold { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #333; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <!-- Alert Messages -->
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <strong>สำเร็จ!</strong>
                <?php
                    if($_GET['success'] == 'added') echo 'เพิ่มสมาชิกสำเร็จ!';
                    if($_GET['success'] == 'updated') echo 'แก้ไขข้อมูลสมาชิกสำเร็จ!';
                    if($_GET['success'] == 'deleted') echo 'ลบสมาชิกสำเร็จ!';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>ผิดพลาด!</strong>
                <?php
                    if($_GET['error'] == 'email_exists') echo 'Email นี้มีอยู่ในระบบแล้ว!';
                    if($_GET['error'] == 'password_mismatch') echo 'รหัสผ่านไม่ตรงกัน!';
                    if($_GET['error'] == 'failed') echo 'ดำเนินการไม่สำเร็จ กรุณาลองใหม่!';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ฟอร์มค้นหา -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-10">
                        <label class="form-label">ค้นหา</label>
                        <input type="text" name="search" class="form-control" placeholder="ค้นหาจาก ชื่อ, นามสกุล, Email, เบอร์โทร" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">🔍 ค้นหา</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ตารางสมาชิก -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">👥 รายการสมาชิก (<?php echo mysqli_num_rows($result); ?> คน)</h4>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                ➕ เพิ่มสมาชิก
            </button>
        </div>
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%">Email</th>
                            <th width="15%">ชื่อ-นามสกุล</th>
                            <th width="10%">เบอร์โทร</th>
                            <th width="10%">จังหวัด</th>
                            <th width="8%">ระดับ</th>
                            <th width="8%">แต้ม</th>
                            <th width="8%">สถานะ</th>
                            <th width="10%">วันที่สมัคร</th>
                            <th width="11%" class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while($user = mysqli_fetch_assoc($result)):
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td><?php echo htmlspecialchars($user['province']); ?></td>
                            <td>
                                <?php
                                $badge_class = 'badge-bronze';
                                if($user['user_level'] == 'Silver') $badge_class = 'badge-silver';
                                if($user['user_level'] == 'Gold') $badge_class = 'badge-gold';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $user['user_level']; ?></span>
                            </td>
                            <td><?php echo number_format($user['points']); ?></td>
                            <td>
                                <?php if($user['status'] == 'active'): ?>
                                    <span class="badge bg-success">ใช้งานได้</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">ระงับการใช้งาน</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td class="text-center">
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $user['user_id']; ?>" title="ดูข้อมูล">👁️</button>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $user['user_id']; ?>" title="แก้ไข">✏️</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')" title="ลบ">🗑️</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========== Modal เพิ่มสมาชิก (1 Modal) ========== -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="sql/user_add.php">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">➕ เพิ่มสมาชิกใหม่</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" minlength="6" required>
                                    <small class="text-muted">ความยาวอย่างน้อย 6 ตัวอักษร</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">เบอร์โทร</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ที่อยู่</label>
                                    <textarea name="address" class="form-control" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">จังหวัด</label>
                                    <input type="text" name="province" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">เขต/อำเภอ</label>
                                    <input type="text" name="district" class="form-control">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ระดับสมาชิก <span class="text-danger">*</span></label>
                                    <select name="user_level" class="form-select" required>
                                        <option value="Bronze" selected>Bronze</option>
                                        <option value="Silver">Silver</option>
                                        <option value="Gold">Gold</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select" required>
                                        <option value="active" selected>ใช้งานได้</option>
                                        <option value="suspended">ระงับการใช้งาน</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success">💾 บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== Loop Modal ดูข้อมูล ========== -->
    <?php
    mysqli_data_seek($result, 0);
    while($user = mysqli_fetch_assoc($result)):
        // ดึงข้อมูลเพิ่มเติม
        $user_id = $user['user_id'];
        $bookings_result = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id=$user_id");
        $bookings = mysqli_fetch_assoc($bookings_result)['c'];
        $co2_result = mysqli_query($conn, "SELECT COALESCE(SUM(co2_reduced), 0) as total FROM carbon_footprint WHERE user_id=$user_id");
        $co2 = mysqli_fetch_assoc($co2_result)['total'];
    ?>
    <div class="modal fade" id="viewModal<?php echo $user['user_id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">👁️ ข้อมูลสมาชิก</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">📋 ข้อมูลส่วนตัว</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">ID:</th>
                                    <td><?php echo $user['user_id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>ชื่อ-นามสกุล:</th>
                                    <td><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>เบอร์โทร:</th>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                </tr>
                            </table>

                            <h6 class="text-primary mt-3">📍 ที่อยู่</h6>
                            <p class="ms-2"><?php echo nl2br(htmlspecialchars($user['address'])); ?><br>
                            <?php echo htmlspecialchars($user['district'] . ', ' . $user['province']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">⭐ ระดับสมาชิก & สถิติ</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">ระดับ:</th>
                                    <td>
                                        <?php
                                        $badge_class = 'badge-bronze';
                                        if($user['user_level'] == 'Silver') $badge_class = 'badge-silver';
                                        if($user['user_level'] == 'Gold') $badge_class = 'badge-gold';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $user['user_level']; ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>แต้มสะสม:</th>
                                    <td><strong><?php echo number_format($user['points']); ?></strong> แต้ม</td>
                                </tr>
                                <tr>
                                    <th>จำนวนการจอง:</th>
                                    <td><?php echo number_format($bookings); ?> ครั้ง</td>
                                </tr>
                                <tr>
                                    <th>CO2 ที่ช่วยลด:</th>
                                    <td><span class="text-success"><strong><?php echo number_format($co2, 2); ?></strong> kg</span></td>
                                </tr>
                                <tr>
                                    <th>สถานะ:</th>
                                    <td>
                                        <?php if($user['status'] == 'active'): ?>
                                            <span class="badge bg-success">ใช้งานได้</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ระงับการใช้งาน</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>วันที่สมัคร:</th>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($user['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

    <!-- ========== Loop Modal แก้ไข ========== -->
    <?php
    mysqli_data_seek($result, 0);
    while($user = mysqli_fetch_assoc($result)):
    ?>
    <div class="modal fade" id="editModal<?php echo $user['user_id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="sql/user_edit.php">
                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">

                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">✏️ แก้ไขข้อมูลสมาชิก</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    <small class="text-muted">Email ไม่สามารถแก้ไขได้</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">รหัสผ่านใหม่</label>
                                    <input type="password" name="password" class="form-control" minlength="6">
                                    <small class="text-muted">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">เบอร์โทร</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ที่อยู่</label>
                                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">จังหวัด</label>
                                    <input type="text" name="province" class="form-control" value="<?php echo htmlspecialchars($user['province']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">เขต/อำเภอ</label>
                                    <input type="text" name="district" class="form-control" value="<?php echo htmlspecialchars($user['district']); ?>">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">ระดับสมาชิก <span class="text-danger">*</span></label>
                                    <select name="user_level" class="form-select" required>
                                        <option value="Bronze" <?php echo $user['user_level'] == 'Bronze' ? 'selected' : ''; ?>>Bronze</option>
                                        <option value="Silver" <?php echo $user['user_level'] == 'Silver' ? 'selected' : ''; ?>>Silver</option>
                                        <option value="Gold" <?php echo $user['user_level'] == 'Gold' ? 'selected' : ''; ?>>Gold</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">แต้มสะสม</label>
                                    <input type="number" name="points" class="form-control" value="<?php echo $user['points']; ?>" min="0">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select" required>
                                        <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>ใช้งานได้</option>
                                        <option value="suspended" <?php echo $user['status'] == 'suspended' ? 'selected' : ''; ?>>ระงับการใช้งาน</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-warning">💾 บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteUser(id, name) {
            if (confirm('คุณแน่ใจว่าต้องการลบสมาชิก "' + name + '" ?\n\nข้อมูลทั้งหมดที่เกี่ยวข้อง (การจอง, ธุรกรรม) จะถูกลบด้วย!\nการกระทำนี้ไม่สามารถย้อนกลับได้!')) {
                window.location.href = 'sql/user_delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>
