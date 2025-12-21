<?php
require_once '../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// ดึงข้อมูลโปรโมชั่น
$sql = "SELECT * FROM promotions ORDER BY start_date DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการโปรโมชั่น - Green Digital</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>🎁 จัดการโปรโมชั่น</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                + เพิ่มโปรโมชั่น
            </button>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php
                if($_GET['success'] == 'added') echo 'เพิ่มโปรโมชั่นสำเร็จ';
                elseif($_GET['success'] == 'updated') echo 'แก้ไขโปรโมชั่นสำเร็จ';
                elseif($_GET['success'] == 'deleted') echo 'ลบโปรโมชั่นสำเร็จ';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>ชื่อโปรโมชั่น</th>
                                <th>รหัสส่วนลด</th>
                                <th>ส่วนลด</th>
                                <th>ระยะเวลา</th>
                                <th>สถานะ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result && mysqli_num_rows($result) > 0): ?>
                                <?php while($promo = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $promo['promotion_id']; ?></td>
                                        <td><?php echo $promo['promotion_name']; ?></td>
                                        <td><code><?php echo $promo['code']; ?></code></td>
                                        <td>
                                            <?php if($promo['discount_type'] == 'percentage'): ?>
                                                <?php echo $promo['discount_value']; ?>%
                                            <?php else: ?>
                                                <?php echo number_format($promo['discount_value'], 2); ?> บาท
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($promo['start_date'])); ?><br>
                                            <small class="text-muted">ถึง <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if($promo['is_active'] == 1): ?>
                                                <span class="badge bg-success">ใช้งาน</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">ไม่ใช้งาน</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $promo['promotion_id']; ?>">ดู</button>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $promo['promotion_id']; ?>">แก้ไข</button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $promo['promotion_id']; ?>">ลบ</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">ไม่มีข้อมูลโปรโมชั่น</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มโปรโมชั่น -->
    <div class="modal fade" id="addModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มโปรโมชั่น</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="sql/promotion_add.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ชื่อโปรโมชั่น <span class="text-danger">*</span></label>
                            <input type="text" name="promotion_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รหัสส่วนลด <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" required>
                            <small class="text-muted">ตัวอย่าง: SAVE20, NEWUSER</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ประเภทส่วนลด <span class="text-danger">*</span></label>
                                <select name="discount_type" class="form-select" required>
                                    <option value="percentage">เปอร์เซ็นต์</option>
                                    <option value="fixed">จำนวนเงิน</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">มูลค่าส่วนลด <span class="text-danger">*</span></label>
                                <input type="number" name="discount_value" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ยอดขั้นต่ำ</label>
                            <input type="number" name="min_purchase" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">จำนวนครั้งที่ใช้ได้</label>
                            <input type="number" name="usage_limit" class="form-control">
                            <small class="text-muted">เว้นว่างไว้หากไม่จำกัด</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">วันที่เริ่มต้น <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">วันที่สิ้นสุด <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select name="is_active" class="form-select" required>
                                <option value="1">ใช้งาน</option>
                                <option value="0">ไม่ใช้งาน</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
    if($result && mysqli_num_rows($result) > 0):
    mysqli_data_seek($result, 0);
    while($promo = mysqli_fetch_assoc($result)):
    ?>
        <!-- Modal ดูโปรโมชั่น -->
        <div class="modal fade" id="viewModal<?php echo $promo['promotion_id']; ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">รายละเอียดโปรโมชั่น</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 40%;">รหัส:</th>
                                <td><?php echo $promo['promotion_id']; ?></td>
                            </tr>
                            <tr>
                                <th>ชื่อโปรโมชั่น:</th>
                                <td><?php echo $promo['promotion_name']; ?></td>
                            </tr>
                            <tr>
                                <th>รหัสส่วนลด:</th>
                                <td><code><?php echo $promo['code']; ?></code></td>
                            </tr>
                            <tr>
                                <th>คำอธิบาย:</th>
                                <td><?php echo $promo['description'] ? $promo['description'] : '-'; ?></td>
                            </tr>
                            <tr>
                                <th>ส่วนลด:</th>
                                <td>
                                    <?php if($promo['discount_type'] == 'percentage'): ?>
                                        <?php echo $promo['discount_value']; ?>%
                                    <?php else: ?>
                                        <?php echo number_format($promo['discount_value'], 2); ?> บาท
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>ยอดขั้นต่ำ:</th>
                                <td><?php echo number_format($promo['min_purchase'], 2); ?> บาท</td>
                            </tr>
                            <tr>
                                <th>จำกัดการใช้:</th>
                                <td>
                                    <?php if($promo['usage_limit']): ?>
                                        <?php echo $promo['usage_limit']; ?> ครั้ง
                                        (ใช้ไปแล้ว <?php echo $promo['used_count']; ?> ครั้ง)
                                    <?php else: ?>
                                        ไม่จำกัด (ใช้ไปแล้ว <?php echo $promo['used_count']; ?> ครั้ง)
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>วันที่เริ่มต้น:</th>
                                <td><?php echo date('d/m/Y', strtotime($promo['start_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>วันที่สิ้นสุด:</th>
                                <td><?php echo date('d/m/Y', strtotime($promo['end_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>สถานะ:</th>
                                <td>
                                    <?php if($promo['is_active'] == 1): ?>
                                        <span class="badge bg-success">ใช้งาน</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ไม่ใช้งาน</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal แก้ไขโปรโมชั่น -->
        <div class="modal fade" id="editModal<?php echo $promo['promotion_id']; ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">แก้ไขโปรโมชั่น</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="sql/promotion_edit.php" method="POST">
                        <input type="hidden" name="promotion_id" value="<?php echo $promo['promotion_id']; ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">ชื่อโปรโมชั่น <span class="text-danger">*</span></label>
                                <input type="text" name="promotion_name" class="form-control" value="<?php echo $promo['promotion_name']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">รหัสส่วนลด <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="<?php echo $promo['code']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">คำอธิบาย</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo $promo['description']; ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ประเภทส่วนลด <span class="text-danger">*</span></label>
                                    <select name="discount_type" class="form-select" required>
                                        <option value="percentage" <?php if($promo['discount_type']=='percentage') echo 'selected'; ?>>เปอร์เซ็นต์</option>
                                        <option value="fixed" <?php if($promo['discount_type']=='fixed') echo 'selected'; ?>>จำนวนเงิน</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">มูลค่าส่วนลด <span class="text-danger">*</span></label>
                                    <input type="number" name="discount_value" class="form-control" step="0.01" value="<?php echo $promo['discount_value']; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ยอดขั้นต่ำ</label>
                                <input type="number" name="min_purchase" class="form-control" step="0.01" value="<?php echo $promo['min_purchase']; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">จำนวนครั้งที่ใช้ได้</label>
                                <input type="number" name="usage_limit" class="form-control" value="<?php echo $promo['usage_limit']; ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">วันที่เริ่มต้น <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $promo['start_date']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">วันที่สิ้นสุด <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo $promo['end_date']; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                                <select name="is_active" class="form-select" required>
                                    <option value="1" <?php if($promo['is_active']==1) echo 'selected'; ?>>ใช้งาน</option>
                                    <option value="0" <?php if($promo['is_active']==0) echo 'selected'; ?>>ไม่ใช้งาน</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal ลบโปรโมชั่น -->
        <div class="modal fade" id="deleteModal<?php echo $promo['promotion_id']; ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">ยืนยันการลบ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="sql/promotion_delete.php" method="POST">
                        <input type="hidden" name="promotion_id" value="<?php echo $promo['promotion_id']; ?>">
                        <div class="modal-body">
                            <p>คุณต้องการลบโปรโมชั่น "<strong><?php echo $promo['promotion_name']; ?></strong>" หรือไม่?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-danger">ลบ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endwhile; endif; ?>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
