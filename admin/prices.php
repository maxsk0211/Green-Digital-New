<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Query ราคาทั้งหมด พร้อมชื่อประเภทขยะ
$sql = "SELECT p.*, rt.type_name
        FROM prices p
        LEFT JOIN recycle_types rt ON p.type_id = rt.type_id
        ORDER BY rt.type_name ASC, p.effective_date DESC";
$result = mysqli_query($conn, $sql);

// Query ประเภทขยะสำหรับ dropdown
$types_sql = "SELECT * FROM recycle_types WHERE status = 'active' ORDER BY type_name ASC";
$types_result = mysqli_query($conn, $types_sql);

// เก็บข้อมูลใน array
$prices_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $prices_data[] = $row;
}

$types_data = [];
while ($row = mysqli_fetch_assoc($types_result)) {
    $types_data[] = $row;
}

// Config สถานะ
$status_config = [
    '1' => ['class' => 'bg-success', 'text' => 'ใช้งาน'],
    '0' => ['class' => 'bg-secondary', 'text' => 'ไม่ใช้งาน']
];

// Config ข้อความสำเร็จ
$success_messages = [
    'added' => 'เพิ่มราคาสำเร็จ!',
    'updated' => 'แก้ไขราคาสำเร็จ!',
    'deleted' => 'ลบราคาสำเร็จ!'
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
    <title>จัดการราคา - Green Digital</title>
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
            <h3>จัดการราคา</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPriceModal">
                เพิ่มราคาใหม่
            </button>
        </div>

        <!-- Prices Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>ประเภทขยะ</th>
                                <th width="150">ราคา (บาท/กก.)</th>
                                <th width="150">วันที่มีผล</th>
                                <th width="120">สถานะ</th>
                                <th width="150" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($prices_data) > 0): ?>
                                <?php $i = 1; foreach($prices_data as $price): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><strong><?= $price['type_name'] ?></strong></td>
                                        <td class="text-end">
                                            <span class="badge bg-success" style="font-size: 1rem;">
                                                <?= number_format($price['price_per_kg'], 2) ?> บาท
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($price['effective_date'])) ?></td>
                                        <td>
                                            <?php
                                            $status_key = (string)$price['is_current'];
                                            $config = $status_config[$status_key] ?? $status_config['0'];
                                            ?>
                                            <span class="badge <?= $config['class'] ?>"><?= $config['text'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $price['price_id'] ?>">ดู</button>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $price['price_id'] ?>">แก้ไข</button>
                                            <button class="btn btn-danger btn-sm" onclick="deletePrice(<?= $price['price_id'] ?>)">ลบ</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">ยังไม่มีข้อมูลราคา</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php foreach($prices_data as $price): ?>
        <!-- View Modal -->
        <div class="modal fade" id="viewModal<?= $price['price_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">รายละเอียดราคา</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table">
                            <tr>
                                <th width="150">ประเภทขยะ:</th>
                                <td><?= $price['type_name'] ?></td>
                            </tr>
                            <tr>
                                <th>ราคา (บาท/กก.):</th>
                                <td class="text-success fw-bold"><?= number_format($price['price_per_kg'], 2) ?> บาท</td>
                            </tr>
                            <tr>
                                <th>วันที่มีผล:</th>
                                <td><?= date('d/m/Y', strtotime($price['effective_date'])) ?></td>
                            </tr>
                            <tr>
                                <th>สถานะ:</th>
                                <td>
                                    <?php
                                    $status_key = (string)$price['is_current'];
                                    $config = $status_config[$status_key] ?? $status_config['0'];
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
        <div class="modal fade" id="editModal<?= $price['price_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="sql/price_edit.php" method="POST">
                        <input type="hidden" name="price_id" value="<?= $price['price_id'] ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">แก้ไขราคา</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">ประเภทขยะ <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select" required>
                                    <?php foreach($types_data as $type): ?>
                                        <option value="<?= $type['type_id'] ?>" <?= $price['type_id']==$type['type_id'] ? 'selected' : '' ?>>
                                            <?= $type['type_name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ราคา (บาท/กก.) <span class="text-danger">*</span></label>
                                <input type="number" name="price_per_kg" class="form-control" step="0.01" min="0" value="<?= $price['price_per_kg'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">วันที่มีผล <span class="text-danger">*</span></label>
                                <input type="date" name="effective_date" class="form-control" value="<?= $price['effective_date'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                                <select name="is_current" class="form-select" required>
                                    <?php foreach($status_config as $key => $config): ?>
                                        <option value="<?= $key ?>" <?= $price['is_current']==$key ? 'selected' : '' ?>>
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

    <!-- Add Price Modal -->
    <div class="modal fade" id="addPriceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="sql/price_add.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่มราคาใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ประเภทขยะ <span class="text-danger">*</span></label>
                            <select name="type_id" class="form-select" required>
                                <option value="">-- เลือกประเภทขยะ --</option>
                                <?php foreach($types_data as $type): ?>
                                    <option value="<?= $type['type_id'] ?>"><?= $type['type_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ราคา (บาท/กก.) <span class="text-danger">*</span></label>
                            <input type="number" name="price_per_kg" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">วันที่มีผล <span class="text-danger">*</span></label>
                            <input type="date" name="effective_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select name="is_current" class="form-select" required>
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
        function deletePrice(id) {
            if(confirm('ต้องการลบราคานี้หรือไม่?')) {
                window.location.href = 'sql/price_delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>
