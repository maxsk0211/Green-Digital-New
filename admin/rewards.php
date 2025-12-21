<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Query ของรางวัลทั้งหมด
$sql = "SELECT r.*,
        (SELECT COUNT(*) FROM reward_redemptions WHERE reward_id = r.reward_id) as total_redeemed
        FROM rewards r
        ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $sql);

// เก็บข้อมูลใน array
$rewards_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rewards_data[] = $row;
}

// Config หมวดหมู่
$category_config = [
    'food' => 'อาหาร',
    'product' => 'สินค้า',
    'voucher' => 'คูปอง',
    'discount' => 'ส่วนลด'
];

// Config สถานะ
$status_config = [
    'active' => ['class' => 'bg-success', 'text' => 'เปิดใช้'],
    'out_of_stock' => ['class' => 'bg-danger', 'text' => 'หมดสต็อก'],
    'inactive' => ['class' => 'bg-secondary', 'text' => 'ปิดใช้']
];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการของรางวัล - Green Digital</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <!-- Alert Messages -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>จัดการของรางวัล</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRewardModal">
                เพิ่มของรางวัล
            </button>
        </div>

        <!-- Rewards Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>ชื่อของรางวัล</th>
                                <th width="100">แต้มที่ใช้</th>
                                <th width="100">หมวดหมู่</th>
                                <th width="100">สต็อก</th>
                                <th width="100">ถูกแลกแล้ว</th>
                                <th width="100">สถานะ</th>
                                <th width="180" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($rewards_data) > 0): ?>
                                <?php $i = 1; foreach($rewards_data as $reward): ?>
                                    <?php
                                    // กำหนดสถานะสต็อก
                                    $stock_quantity = $reward['stock_quantity'] ?? 0;
                                    $stock_text = $stock_quantity == 0 ? 'ไม่จำกัด' : $stock_quantity;
                                    $stock_class = 'badge bg-success';

                                    if ($stock_quantity > 0 && $stock_quantity <= 5) {
                                        $stock_class = 'badge bg-warning';
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($reward['reward_name']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($reward['description'], 0, 50)) ?>...</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= number_format($reward['points_required']) ?></span>
                                        </td>
                                        <td><?= $category_config[$reward['category']] ?? '-' ?></td>
                                        <td>
                                            <span class="<?= $stock_class ?>"><?= $stock_text ?></span>
                                        </td>
                                        <td class="text-center"><?= number_format($reward['total_redeemed']) ?></td>
                                        <td>
                                            <?php
                                            $status = $reward['status'] ?? 'inactive';
                                            $config = $status_config[$status] ?? $status_config['inactive'];
                                            ?>
                                            <span class="badge <?= $config['class'] ?>"><?= $config['text'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $reward['reward_id'] ?>">ดู</button>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $reward['reward_id'] ?>">แก้ไข</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteReward(<?= $reward['reward_id'] ?>)">ลบ</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">ยังไม่มีข้อมูลของรางวัล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php foreach($rewards_data as $reward): ?>
        <!-- View Modal -->
        <div class="modal fade" id="viewModal<?= $reward['reward_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">รายละเอียดของรางวัล</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table">
                            <tr>
                                <th width="150">ชื่อของรางวัล:</th>
                                <td><?= htmlspecialchars($reward['reward_name']) ?></td>
                            </tr>
                            <tr>
                                <th>รายละเอียด:</th>
                                <td><?= htmlspecialchars($reward['description']) ?></td>
                            </tr>
                            <tr>
                                <th>แต้มที่ใช้:</th>
                                <td><span class="points-badge"><?= number_format($reward['points_required']) ?> แต้ม</span></td>
                            </tr>
                            <tr>
                                <th>หมวดหมู่:</th>
                                <td><?= $category_config[$reward['category']] ?? '-' ?></td>
                            </tr>
                            <tr>
                                <th>จำนวนคงเหลือ:</th>
                                <td><?= $reward['stock_quantity'] == 0 ? 'ไม่จำกัด' : number_format($reward['stock_quantity']) ?></td>
                            </tr>
                            <tr>
                                <th>สถานะ:</th>
                                <td>
                                    <?php
                                    $status = $reward['status'] ?? 'inactive';
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
        <div class="modal fade" id="editModal<?= $reward['reward_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="sql/reward_edit.php" method="POST">
                        <input type="hidden" name="reward_id" value="<?= $reward['reward_id'] ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">แก้ไขของรางวัล</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">ชื่อของรางวัล <span class="text-danger">*</span></label>
                                <input type="text" name="reward_name" class="form-control" value="<?= htmlspecialchars($reward['reward_name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($reward['description']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">แต้มที่ใช้แลก <span class="text-danger">*</span></label>
                                <input type="number" name="points_required" class="form-control" min="1" value="<?= $reward['points_required'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    <?php foreach ($category_config as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= $reward['category']==$key ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">จำนวนสต็อก <span class="text-danger">*</span></label>
                                <input type="number" name="stock_quantity" class="form-control" min="0" value="<?= $reward['stock_quantity'] ?>" required>
                                <small class="text-muted">ใส่ 0 = ไม่จำกัดจำนวน</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <?php foreach ($status_config as $key => $config): ?>
                                        <option value="<?= $key ?>" <?= (isset($reward['status']) && $reward['status']==$key) ? 'selected' : '' ?>>
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

    <!-- Add Reward Modal -->
    <div class="modal fade" id="addRewardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="sql/reward_add.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่มของรางวัลใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ชื่อของรางวัล <span class="text-danger">*</span></label>
                            <input type="text" name="reward_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียด</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">แต้มที่ใช้แลก <span class="text-danger">*</span></label>
                            <input type="number" name="points_required" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <?php foreach ($category_config as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">จำนวนสต็อก <span class="text-danger">*</span></label>
                            <input type="number" name="stock_quantity" class="form-control" min="0" value="0" required>
                            <small class="text-muted">ใส่ 0 = ไม่จำกัดจำนวน</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active"><?= $status_config['active']['text'] ?></option>
                                <option value="inactive"><?= $status_config['inactive']['text'] ?></option>
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
        function deleteReward(id) {
            if(confirm('ต้องการลบของรางวัลนี้หรือไม่?')) {
                window.location.href = 'sql/reward_delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>
