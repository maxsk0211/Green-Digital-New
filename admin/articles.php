<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// รับค่าการค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query บทความทั้งหมด พร้อมคำนวณสถานะแสดงผล
$sql = "SELECT a.*, ad.full_name as author_name,
        CASE
            WHEN a.status = 'draft' THEN 'draft'
            WHEN NOW() < a.published_start THEN 'scheduled'
            WHEN a.published_end IS NOT NULL AND NOW() > a.published_end THEN 'expired'
            ELSE 'active'
        END as display_status
        FROM articles a
        LEFT JOIN admins ad ON a.author_id = ad.admin_id
        WHERE 1=1";

// เพิ่มเงื่อนไขการค้นหา
if (!empty($search)) {
    $search_escaped = $conn->real_escape_string($search);
    $sql .= " AND (a.title LIKE '%$search_escaped%'
              OR a.content LIKE '%$search_escaped%'
              OR a.category LIKE '%$search_escaped%'
              OR ad.full_name LIKE '%$search_escaped%')";
}

$sql .= " ORDER BY a.created_at DESC";
$result = mysqli_query($conn, $sql);

// ตรวจสอบว่า query สำเร็จหรือไม่
if (!$result) {
    die("Error in query: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบทความ - Green Digital</title>
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
                    if($_GET['success'] == 'added') echo 'เพิ่มบทความสำเร็จ!';
                    if($_GET['success'] == 'updated') echo 'แก้ไขบทความสำเร็จ!';
                    if($_GET['success'] == 'deleted') echo 'ลบบทความสำเร็จ!';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>เกิดข้อผิดพลาด!</strong>
                <?php
                    if($_GET['error'] == 'failed') echo 'ไม่สามารถดำเนินการได้ กรุณาลองใหม่อีกครั้ง';
                    if($_GET['error'] == 'upload_failed') echo 'อัปโหลดรูปภาพไม่สำเร็จ';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>📚 จัดการบทความ</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addArticleModal">
                ➕ เพิ่มบทความใหม่
            </button>
        </div>

        <!-- Search Box -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="ค้นหา: หัวข้อ, เนื้อหา, หมวดหมู่, ผู้เขียน..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">🔍 ค้นหา</button>
                        <?php if(!empty($search)): ?>
                            <a href="articles.php" class="btn btn-secondary">ล้าง</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Articles Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="80">รูปภาพ</th>
                                <th>หัวข้อ</th>
                                <th>หมวดหมู่</th>
                                <th>ผู้เขียน</th>
                                <th width="140">สถานะแสดงผล</th>
                                <th width="180">ช่วงเวลาแสดง</th>
                                <th width="150" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($article = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>
                                            <?php if($article['image_url']): ?>
                                                <img src="<?php echo "../" . $article['image_url']; ?>" class="img-thumbnail">
                                            <?php else: ?>
                                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center">📄</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($article['title']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $article['category']; ?></span>
                                        </td>
                                        <td><?php echo $article['author_name']; ?></td>
                                        <td>
                                            <?php
                                            switch($article['display_status']) {
                                                case 'active':
                                                    echo '<span class="badge bg-success">กำลังแสดง</span>';
                                                    break;
                                                case 'scheduled':
                                                    echo '<span class="badge bg-warning text-dark">รอแสดง</span>';
                                                    break;
                                                case 'expired':
                                                    echo '<span class="badge bg-secondary">หมดอายุ</span>';
                                                    break;
                                                case 'draft':
                                                    echo '<span class="badge bg-secondary">แบบร่าง</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($article['status'] == 'published'): ?>
                                                <small>
                                                    <strong>เริ่ม:</strong> <?php echo date('d/m/Y H:i', strtotime($article['published_start'])); ?><br>
                                                    <strong>สิ้นสุด:</strong>
                                                    <?php
                                                    if($article['published_end']) {
                                                        echo date('d/m/Y H:i', strtotime($article['published_end']));
                                                    } else {
                                                        echo '<span class="text-muted">ไม่จำกัด</span>';
                                                    }
                                                    ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $article['article_id']; ?>">👁️</button>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $article['article_id']; ?>">✏️</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteArticle(<?php echo $article['article_id']; ?>)">🗑️</button>
                                        </td>
                                    </tr>

                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewModal<?php echo $article['article_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">📖 รายละเอียดบทความ</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php if($article['image_url']): ?>
                                                        <img src="<?php echo "../" .$article['image_url']; ?>" class="img-fluid mb-3">
                                                    <?php endif; ?>
                                                    <h4><?php echo htmlspecialchars($article['title']); ?></h4>
                                                    <p class="text-muted">
                                                        <small>
                                                            หมวดหมู่: <?php echo $article['category']; ?> |
                                                            ผู้เขียน: <?php echo $article['author_name']; ?> |
                                                            <?php echo date('d/m/Y H:i', strtotime($article['created_at'])); ?>
                                                        </small>
                                                    </p>
                                                    <hr>
                                                    <div><?php echo nl2br(htmlspecialchars($article['content'])); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo $article['article_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form action="sql/article_edit.php" method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="article_id" value="<?php echo $article['article_id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">✏️ แก้ไขบทความ</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">หัวข้อบทความ <span class="text-danger">*</span></label>
                                                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($article['title']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                                                            <select name="category" class="form-select" required>
                                                                <option value="การรีไซเคิล" <?php if($article['category']=='การรีไซเคิล') echo 'selected'; ?>>การรีไซเคิล</option>
                                                                <option value="สิ่งแวดล้อม" <?php if($article['category']=='สิ่งแวดล้อม') echo 'selected'; ?>>สิ่งแวดล้อม</option>
                                                                <option value="การลดขยะ" <?php if($article['category']=='การลดขยะ') echo 'selected'; ?>>การลดขยะ</option>
                                                                <option value="เทคโนโลยีสีเขียว" <?php if($article['category']=='เทคโนโลยีสีเขียว') echo 'selected'; ?>>เทคโนโลยีสีเขียว</option>
                                                                <option value="อื่นๆ" <?php if($article['category']=='อื่นๆ') echo 'selected'; ?>>อื่นๆ</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">เนื้อหา <span class="text-danger">*</span></label>
                                                            <textarea name="content" class="form-control" rows="8" required><?php echo htmlspecialchars($article['content']); ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">รูปภาพ (ไม่เลือกหากไม่ต้องการเปลี่ยน)</label>
                                                            <?php if($article['image_url']): ?>
                                                                <div class="mb-2">
                                                                    <img src="<?php echo "../" .$article['image_url']; ?>" class="img-thumbnail">
                                                                </div>
                                                            <?php endif; ?>
                                                            <input type="file" name="image" class="form-control" accept="image/*">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                                                            <select name="status" class="form-select" id="status_edit_<?php echo $article['article_id']; ?>" required onchange="toggleScheduleFields('edit', <?php echo $article['article_id']; ?>)">
                                                                <option value="draft" <?php if($article['status']=='draft') echo 'selected'; ?>>แบบร่าง</option>
                                                                <option value="published" <?php if($article['status']=='published') echo 'selected'; ?>>เผยแพร่</option>
                                                            </select>
                                                        </div>

                                                        <div id="schedule_fields_edit_<?php echo $article['article_id']; ?>" class="<?php echo $article['status']=='draft' ? 'd-none' : ''; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">วันเวลาเริ่มแสดง <span class="text-danger">*</span></label>
                                                                <input type="datetime-local" name="published_start" class="form-control"
                                                                    value="<?php echo $article['published_start'] ? date('Y-m-d\TH:i', strtotime($article['published_start'])) : date('Y-m-d\TH:i'); ?>">
                                                                <small class="text-muted">ถ้าไม่ระบุ จะใช้เวลาปัจจุบัน</small>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">วันเวลาสิ้นสุดการแสดง</label>
                                                                <input type="datetime-local" name="published_end" class="form-control" id="published_end_edit_<?php echo $article['article_id']; ?>"
                                                                    value="<?php echo $article['published_end'] ? date('Y-m-d\TH:i', strtotime($article['published_end'])) : ''; ?>"
                                                                    <?php echo !$article['published_end'] ? 'disabled' : ''; ?>>
                                                                <div class="form-check mt-2">
                                                                    <input class="form-check-input" type="checkbox" id="no_expiry_edit_<?php echo $article['article_id']; ?>"
                                                                        <?php echo !$article['published_end'] ? 'checked' : ''; ?>
                                                                        onchange="toggleEndDate('edit', <?php echo $article['article_id']; ?>)">
                                                                    <label class="form-check-label" for="no_expiry_edit_<?php echo $article['article_id']; ?>">
                                                                        ไม่มีวันหมดอายุ
                                                                    </label>
                                                                </div>
                                                            </div>
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
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">ยังไม่มีบทความ</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Article Modal -->
    <div class="modal fade" id="addArticleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="sql/article_add.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">➕ เพิ่มบทความใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">หัวข้อบทความ <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <option value="การรีไซเคิล">การรีไซเคิล</option>
                                <option value="สิ่งแวดล้อม">สิ่งแวดล้อม</option>
                                <option value="การลดขยะ">การลดขยะ</option>
                                <option value="เทคโนโลยีสีเขียว">เทคโนโลยีสีเขียว</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">เนื้อหา <span class="text-danger">*</span></label>
                            <textarea name="content" class="form-control" rows="8" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รูปภาพ</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">รองรับ JPG, PNG, GIF (ขนาดไม่เกิน 5MB)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" id="status_add" required onchange="toggleScheduleFields('add', 0)">
                                <option value="draft">แบบร่าง</option>
                                <option value="published">เผยแพร่</option>
                            </select>
                        </div>

                        <div id="schedule_fields_add_0" class="d-none">
                            <div class="mb-3">
                                <label class="form-label">วันเวลาเริ่มแสดง <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="published_start" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
                                <small class="text-muted">ถ้าไม่ระบุ จะใช้เวลาปัจจุบัน</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">วันเวลาสิ้นสุดการแสดง</label>
                                <input type="datetime-local" name="published_end" class="form-control" id="published_end_add_0" disabled>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="no_expiry_add_0" checked onchange="toggleEndDate('add', 0)">
                                    <label class="form-check-label" for="no_expiry_add_0">
                                        ไม่มีวันหมดอายุ
                                    </label>
                                </div>
                            </div>
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
        function deleteArticle(id) {
            if(confirm('ต้องการลบบทความนี้หรือไม่?')) {
                window.location.href = 'sql/article_delete.php?id=' + id;
            }
        }

        // Toggle schedule fields based on status
        function toggleScheduleFields(type, id) {
            const statusSelect = document.getElementById('status_' + type + (type === 'add' ? '' : '_' + id));
            const scheduleFields = document.getElementById('schedule_fields_' + type + '_' + id);

            if (statusSelect.value === 'published') {
                scheduleFields.classList.remove('d-none');
            } else {
                scheduleFields.classList.add('d-none');
            }
        }

        // Toggle end date field
        function toggleEndDate(type, id) {
            const checkbox = document.getElementById('no_expiry_' + type + '_' + id);
            const endDateInput = document.getElementById('published_end_' + type + '_' + id);

            if (checkbox.checked) {
                endDateInput.disabled = true;
                endDateInput.value = '';
            } else {
                endDateInput.disabled = false;
            }
        }
    </script>
</body>
</html>
