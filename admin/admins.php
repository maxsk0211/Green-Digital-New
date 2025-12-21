<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Query ข้อมูล Admin ทั้งหมด
$sql = "SELECT * FROM admins ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ดูแลระบบ - Green Digital</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <!-- Alert Messages -->
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <strong>สำเร็จ!</strong>
                <?php
                    if($_GET['success'] == 'added') echo 'เพิ่มผู้ดูแลสำเร็จ!';
                    if($_GET['success'] == 'updated') echo 'แก้ไขข้อมูลสำเร็จ!';
                    if($_GET['success'] == 'deleted') echo 'ลบผู้ดูแลสำเร็จ!';
                    if($_GET['success'] == 'suspended') echo 'ระงับสิทธิ์สำเร็จ!';
                    if($_GET['success'] == 'unsuspended') echo 'ยกเลิกระงับสิทธิ์สำเร็จ!';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>ผิดพลาด!</strong>
                <?php
                    if($_GET['error'] == 'username_exists') echo 'Username นี้มีอยู่ในระบบแล้ว!';
                    if($_GET['error'] == 'password_mismatch') echo 'รหัสผ่านไม่ตรงกัน!';
                    if($_GET['error'] == 'delete_self') echo 'ไม่สามารถลบตัวเองได้!';
                    if($_GET['error'] == 'suspend_self') echo 'ไม่สามารถระงับสิทธิ์ตัวเองได้!';
                    if($_GET['error'] == 'failed') echo 'ดำเนินการไม่สำเร็จ กรุณาลองใหม่!';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ปุ่มเพิ่ม Admin (เฉพาะ Super Admin) -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>📋 รายการผู้ดูแลระบบ</h4>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    ➕ เพิ่มผู้ดูแลระบบ
                </button>
            <?php endif; ?>
        </div>

        <!-- ตาราง Admin -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="13%">Username</th>
                            <th width="18%">ชื่อ-นามสกุล</th>
                            <th width="18%">Email</th>
                            <th width="10%">Role</th>
                            <th width="12%">สถานะ</th>
                            <th width="12%">วันที่สร้าง</th>
                            <th width="12%" class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while($admin = mysqli_fetch_assoc($result)):
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td>
                                <?php if($admin['role'] == 'admin'): ?>
                                    <span class="badge bg-danger">แอดมิน</span>
                                <?php elseif($admin['role'] == 'owner'): ?>
                                    <span class="badge bg-success">เจ้าของร้าน</span>
                                <?php else: ?>
                                    <span class="badge bg-info">พนักงาน</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(isset($admin['status']) && $admin['status'] == 'suspended'): ?>
                                    <span class="badge bg-danger">ระงับการใช้งาน</span>
                                <?php else: ?>
                                    <span class="badge bg-success">ใช้งานได้</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                            <td class="text-center">
                                <!-- ปุ่มดู -->
                                <button class="btn btn-info btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewModal<?php echo $admin['admin_id']; ?>"
                                        title="ดูข้อมูล">
                                    👁️
                                </button>

                                <!-- ปุ่มแก้ไข (เฉพาะ Super Admin) -->
                                <?php if($_SESSION['role'] == 'admin'): ?>
                                    <button class="btn btn-warning btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal<?php echo $admin['admin_id']; ?>"
                                            title="แก้ไข">
                                        ✏️
                                    </button>

                                    <!-- ปุ่มระงับสิทธิ์ (ไม่ให้ระงับตัวเอง) -->
                                    <?php if($admin['admin_id'] != $_SESSION['admin_id']): ?>
                                        <?php if(isset($admin['status']) && $admin['status'] == 'suspended'): ?>
                                            <button class="btn btn-success btn-sm"
                                                    onclick="suspendAdmin(<?php echo $admin['admin_id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>', 'unsuspend')"
                                                    title="ยกเลิกระงับสิทธิ์">
                                                ✅
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-warning btn-sm"
                                                    onclick="suspendAdmin(<?php echo $admin['admin_id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>', 'suspend')"
                                                    title="ระงับสิทธิ์">
                                                ⛔
                                            </button>
                                        <?php endif; ?>

                                        <!-- ปุ่มลบ -->
                                        <button class="btn btn-danger btn-sm"
                                                onclick="deleteAdmin(<?php echo $admin['admin_id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')"
                                                title="ลบ">
                                            🗑️
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========== Modal เพิ่ม Admin (1 Modal) ========== -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="sql/admin_add.php">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">➕ เพิ่มผู้ดูแลระบบ</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required>
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
                            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="staff">พนักงาน</option>
                                <option value="owner">เจ้าของร้าน</option>
                                <option value="admin">แอดมิน</option>
                            </select>
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

    <!-- ========== Loop Modal แก้ไข + Modal ดู (แยกตาม admin_id) ========== -->
    <?php
    $result->data_seek(0); // Reset pointer
    while($admin = mysqli_fetch_assoc($result)):
    ?>

    <!-- Modal ดูข้อมูล -->
    <div class="modal fade" id="viewModal<?php echo $admin['admin_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">👁️ ข้อมูลผู้ดูแลระบบ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">ID:</th>
                            <td><?php echo $admin['admin_id']; ?></td>
                        </tr>
                        <tr>
                            <th>Username:</th>
                            <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>ชื่อ-นามสกุล:</th>
                            <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Role:</th>
                            <td>
                                <?php if($admin['role'] == 'admin'): ?>
                                    <span class="badge bg-purple text-white">แอดมิน</span>
                                <?php elseif($admin['role'] == 'owner'): ?>
                                    <span class="badge bg-success">เจ้าของร้าน</span>
                                <?php else: ?>
                                    <span class="badge bg-info">พนักงาน</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>วันที่สร้าง:</th>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($admin['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไข -->
    <div class="modal fade" id="editModal<?php echo $admin['admin_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="sql/admin_edit.php">
                    <input type="hidden" name="admin_id" value="<?php echo $admin['admin_id']; ?>">

                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">✏️ แก้ไขผู้ดูแลระบบ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" readonly>
                            <small class="text-muted">Username ไม่สามารถแก้ไขได้</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">รหัสผ่านใหม่ (เว้นว่าง = ไม่เปลี่ยน)</label>
                            <input type="password" name="password" class="form-control" minlength="6">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="6">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="staff" <?php echo $admin['role'] == 'staff' ? 'selected' : ''; ?>>พนักงาน</option>
                                <option value="owner" <?php echo $admin['role'] == 'owner' ? 'selected' : ''; ?>>เจ้าของร้าน</option>
                                <option value="admin" <?php echo $admin['role'] == 'admin' ? 'selected' : ''; ?>>แอดมิน</option>
                            </select>
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
        function suspendAdmin(id, username, action) {
            let message = '';
            if (action === 'suspend') {
                message = 'คุณแน่ใจว่าต้องการระงับสิทธิ์ของ "' + username + '" ?';
            } else {
                message = 'คุณแน่ใจว่าต้องการยกเลิกระงับสิทธิ์ของ "' + username + '" ?';
            }

            if (confirm(message)) {
                window.location.href = 'sql/admin_suspend.php?id=' + id + '&action=' + action;
            }
        }

        function deleteAdmin(id, username) {
            if (confirm('คุณแน่ใจว่าต้องการลบผู้ดูแล "' + username + '" ?\n\nการกระทำนี้ไม่สามารถย้อนกลับได้!')) {
                window.location.href = 'sql/admin_delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>
