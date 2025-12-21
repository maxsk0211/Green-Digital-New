<?php
require_once '../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// ดึงข้อมูล Admin
$admin_sql = "SELECT * FROM admins WHERE admin_id = '$admin_id'";
$admin_result = mysqli_query($conn, $admin_sql);
$admin = mysqli_fetch_assoc($admin_result);

// ฟิลเตอร์
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_article = isset($_GET['article_id']) ? intval($_GET['article_id']) : 0;
$filter_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where_conditions = [];

if (!empty($filter_status)) {
    $where_conditions[] = "c.status = '$filter_status'";
}

if ($filter_article > 0) {
    $where_conditions[] = "c.article_id = $filter_article";
}

if (!empty($filter_search)) {
    $where_conditions[] = "(u.first_name LIKE '%$filter_search%' OR u.last_name LIKE '%$filter_search%' OR c.comment_text LIKE '%$filter_search%' OR a.title LIKE '%$filter_search%')";
}

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// ดึงคอมเมนต์ทั้งหมด (รวมทั้ง user และ admin)
$comments_sql = "SELECT c.*,
                 CONCAT(u.first_name, ' ', u.last_name) as user_name,
                 ad.full_name as admin_name,
                 a.title as article_title,
                 a.article_id
                 FROM article_comments c
                 LEFT JOIN users u ON c.user_id = u.user_id
                 LEFT JOIN admins ad ON c.admin_id = ad.admin_id
                 JOIN articles a ON c.article_id = a.article_id
                 $where_clause
                 ORDER BY c.created_at DESC";
$comments = mysqli_query($conn, $comments_sql);

// สถิติ
$stats_sql = "SELECT
              COUNT(*) as total,
              SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
              SUM(CASE WHEN status = 'deleted' THEN 1 ELSE 0 END) as deleted_count,
              SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden_count
              FROM article_comments";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// ดึงรายการบทความสำหรับ dropdown
$articles_sql = "SELECT article_id, title FROM articles ORDER BY title";
$articles_list = mysqli_query($conn, $articles_sql);

// เก็บ comments ใน array
$comments_data = [];
while ($row = mysqli_fetch_assoc($comments)) {
    $comments_data[] = $row;
}

// เก็บ articles ใน array
$articles_data = [];
while ($row = mysqli_fetch_assoc($articles_list)) {
    $articles_data[] = $row;
}

// Config สถานะ
$status_config = [
    'active' => ['class' => 'bg-success', 'text' => 'แสดง'],
    'deleted' => ['class' => 'bg-secondary', 'text' => 'ลบแล้ว'],
    'hidden' => ['class' => 'bg-warning', 'text' => 'ซ่อน']
];

// Config ฟิลเตอร์
$filter_options = [
    'active' => 'กำลังแสดง',
    'deleted' => 'ถูกลบ',
    'hidden' => 'ซ่อน'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการความคิดเห็น - Green Digital Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <h3 class="mb-4">จัดการความคิดเห็นบทความ</h3>

        <!-- ฟิลเตอร์ -->
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3">ค้นหา/ฟิลเตอร์</h5>
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">ค้นหา</label>
                        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($filter_search) ?>" placeholder="ชื่อผู้ใช้ / เนื้อหา / บทความ">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($filter_options as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $filter_status == $key ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">บทความ</label>
                        <select name="article_id" class="form-select">
                            <option value="0">ทั้งหมด</option>
                            <?php foreach ($articles_data as $art): ?>
                                <option value="<?= $art['article_id'] ?>" <?= $filter_article == $art['article_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($art['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">ค้นหา</button>
                            <a href="article_comments.php" class="btn btn-secondary">ล้าง</a>
                        </div>
                    </div>
                </div>
            </form>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                ดำเนินการเรียบร้อยแล้ว
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ตารางคอมเมนต์ -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>ผู้แสดงความคิดเห็น</th>
                                <th>บทความ</th>
                                <th>เนื้อหา</th>
                                <th>วันที่</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($comments_data) > 0): ?>
                                <?php foreach($comments_data as $comment): ?>
                                    <tr>
                                        <td><?= $comment['comment_id'] ?></td>
                                        <td>
                                            <?php if($comment['admin_id']): ?>
                                                <span class="badge bg-primary">Admin</span>
                                                <?= htmlspecialchars($comment['admin_name']) ?>
                                            <?php else: ?>
                                                <?= htmlspecialchars($comment['user_name']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../article_detail.php?id=<?= $comment['article_id'] ?>#comment-<?= $comment['comment_id'] ?>" target="_blank">
                                                <?= htmlspecialchars($comment['article_title']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($comment['comment_text']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></td>
                                        <td>
                                            <?php
                                            $status = $comment['status'];
                                            $config = $status_config[$status] ?? ['class' => 'bg-secondary', 'text' => 'ไม่ทราบ'];
                                            ?>
                                            <span class="badge <?= $config['class'] ?>"><?= $config['text'] ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if($comment['status'] == 'active'): ?>
                                                    <form method="POST" action="sql/comment_hide.php" style="display: inline;">
                                                        <input type="hidden" name="comment_id" value="<?= $comment['comment_id'] ?>">
                                                        <button type="submit" class="btn btn-warning" title="ซ่อน" onclick="return confirm('ยืนยันการซ่อนความคิดเห็น?')">
                                                            ซ่อน
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="sql/comment_restore.php" style="display: inline;">
                                                        <input type="hidden" name="comment_id" value="<?= $comment['comment_id'] ?>">
                                                        <button type="submit" class="btn btn-success" title="แสดง">
                                                            แสดง
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <form method="POST" action="sql/comment_delete.php" style="display: inline;">
                                                    <input type="hidden" name="comment_id" value="<?= $comment['comment_id'] ?>">
                                                    <button type="submit" class="btn btn-danger" title="ลบถาวร" onclick="return confirm('ยืนยันการลบถาวร? ไม่สามารถกู้คืนได้')">
                                                        ลบ
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-muted mb-0">ไม่พบข้อมูล</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3 text-muted">
            <small>รายการทั้งหมด: <?= count($comments_data) ?> รายการ</small>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
