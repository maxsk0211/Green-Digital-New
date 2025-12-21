<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Query ประเภทขยะทั้งหมด
$sql = "SELECT * FROM recycle_types ORDER BY type_name ASC";
$result = mysqli_query($conn, $sql);

// เก็บข้อมูลใน array
$types_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $types_data[] = $row;
}

// Config สถานะ
$status_config = [
    'active' => ['class' => 'bg-success', 'text' => 'ใช้งาน'],
    'inactive' => ['class' => 'bg-secondary', 'text' => 'ปิดใช้งาน']
];

// Config ข้อความสำเร็จ
$success_messages = [
    'added' => 'เพิ่มประเภทขยะสำเร็จ!',
    'updated' => 'แก้ไขประเภทขยะสำเร็จ!',
    'deleted' => 'ลบประเภทขยะสำเร็จ!'
];

// Config ข้อความผิดพลาด
$error_messages = [
    'failed' => 'ไม่สามารถดำเนินการได้ กรุณาลองใหม่อีกครั้ง'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการประเภทขยะ - Green Digital</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <!-- Alert Messages -->
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <strong>สำเร็จ!</strong>
                <?= $success_messages[$_GET['success']] ?? '' ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>เกิดข้อผิดพลาด!</strong>
                <?= $error_messages[$_GET['error']] ?? '' ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>จัดการประเภทขยะ</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                เพิ่มประเภทขยะ
            </button>
        </div>

        <!-- Recycle Types Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>ชื่อประเภท</th>
                                <th>รายละเอียด</th>
                                <th width="150">CO2 ที่ลดได้/kg</th>
                                <th width="120">สถานะ</th>
                                <th width="150" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($types_data) > 0): ?>
                                <?php $i = 1; foreach($types_data as $type): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><strong><?= $type['type_name'] ?></strong></td>
                                        <td><?= $type['description'] ?></td>
                                        <td>
                                            <span class="text-success"><?= number_format($type['co2_reduction'], 2) ?> kg</span>
                                        </td>
                                        <td>
                                            <?php
                                            $status = $type['status'];
                                            $config = $status_config[$status] ?? $status_config['inactive'];
                                            ?>
                                            <span class="badge <?= $config['class'] ?>"><?= $config['text'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $type['type_id'] ?>">ดู</button>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $type['type_id'] ?>">แก้ไข</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteType(<?= $type['type_id'] ?>)">ลบ</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">ยังไม่มีข้อมูลประเภทขยะ</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php foreach($types_data as $type): ?>
        <!-- View Modal -->
        <div class="modal fade" id="viewModal<?= $type['type_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">รายละเอียดประเภทขยะ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table">
                            <tr>
                                <th width="150">ชื่อประเภท:</th>
                                <td><?= $type['type_name'] ?></td>
                            </tr>
                            <tr>
                                <th>รายละเอียด:</th>
                                <td><?= $type['description'] ?></td>
                            </tr>
                            <tr>
                                <th>CO2 ที่ลดได้:</th>
                                <td>
                                    <span class="text-success"><?= number_format($type['co2_reduction'], 2) ?> kg CO2/kg</span>
                                </td>
                            </tr>
                            <tr>
                                <th>สถานะ:</th>
                                <td>
                                    <?php
                                    $status = $type['status'];
                                    $config = $status_config[$status] ?? $status_config['inactive'];
                                    ?>
                                    <span class="badge <?= $config['class'] ?>"><?= $config['text'] ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal<?= $type['type_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="sql/recycle_type_edit.php" method="POST">
                        <input type="hidden" name="type_id" value="<?= $type['type_id'] ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">แก้ไขประเภทขยะ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">ชื่อประเภท <span class="text-danger">*</span></label>
                                <input type="text" name="type_name" class="form-control" value="<?= $type['type_name'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="description" class="form-control" rows="3"><?= $type['description'] ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CO2 ที่ลดได้ (kg CO2/kg) <span class="text-danger">*</span></label>
                                <input type="number" name="co2_reduction" class="form-control" step="0.01" min="0" value="<?= $type['co2_reduction'] ?>" required placeholder="0.00">
                                <small class="text-muted">ระบุปริมาณ CO2 ที่ลดได้ต่อ 1 กิโลกรัม</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <?php foreach($status_config as $key => $config): ?>
                                        <option value="<?= $key ?>" <?= $type['status']==$key ? 'selected' : '' ?>>
                                            <?= $config['text'] ?>
                                        </option>
                                    <?php endforeach; ?>
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
    <?php endforeach; ?>

    <!-- Add Type Modal -->
    <div class="modal fade" id="addTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="sql/recycle_type_add.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่มประเภทขยะใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ชื่อประเภท <span class="text-danger">*</span></label>
                            <input type="text" name="type_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียด</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CO2 ที่ลดได้ (kg CO2/kg) <span class="text-danger">*</span></label>
                            <input type="number" name="co2_reduction" class="form-control" step="0.01" min="0" value="0.00" required placeholder="0.00">
                            <small class="text-muted">ระบุปริมาณ CO2 ที่ลดได้ต่อ 1 กิโลกรัม</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <?php foreach($status_config as $key => $config): ?>
                                    <option value="<?= $key ?>"><?= $config['text'] ?></option>
                                <?php endforeach; ?>
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

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteType(id) {
            if(confirm('ต้องการลบประเภทขยะนี้หรือไม่?')) {
                window.location.href = 'sql/recycle_type_delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>
