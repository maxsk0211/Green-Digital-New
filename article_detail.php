<?php
session_start();
require_once 'config.php';

// ตั้งค่าสำหรับ navbar
$base_url = '';
$current_page = 'articles';

// รับค่า article_id จาก URL
$article_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($article_id) || !is_numeric($article_id)) {
    header("Location: articles.php");
    exit();
}

// เพิ่มจำนวน views
$update_views = "UPDATE articles SET views = views + 1 WHERE article_id = '$article_id'";
mysqli_query($conn, $update_views);

// ดึงข้อมูลบทความ (ตรวจสอบว่าอยู่ในช่วงเวลาแสดงผล)
$sql = "SELECT a.*, ad.full_name as author_name
        FROM articles a
        LEFT JOIN admins ad ON a.author_id = ad.admin_id
        WHERE a.article_id = '$article_id'
        AND a.status = 'published'
        AND NOW() >= a.published_start
        AND (a.published_end IS NULL OR NOW() <= a.published_end)";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    // บทความไม่พบหรือหมดอายุ
    header("Location: articles.php");
    exit();
}

$article = mysqli_fetch_assoc($result);

// ดึงคอมเมนต์ทั้งหมดของบทความนี้ (เฉพาะ active) - รวมทั้ง user และ admin
$comments_sql = "SELECT c.*,
                 u.first_name as user_first_name, u.last_name as user_last_name,
                 a.full_name as admin_name,
                 c.created_at, c.updated_at
                 FROM article_comments c
                 LEFT JOIN users u ON c.user_id = u.user_id
                 LEFT JOIN admins a ON c.admin_id = a.admin_id
                 WHERE c.article_id = '$article_id' AND c.status = 'active'
                 ORDER BY c.created_at ASC";
$comments_result = mysqli_query($conn, $comments_sql);

// นับจำนวนคอมเมนต์
$comment_count_sql = "SELECT COUNT(*) as total FROM article_comments WHERE article_id = '$article_id' AND status = 'active'";
$comment_count_result = mysqli_query($conn, $comment_count_sql);
$comment_count = mysqli_fetch_assoc($comment_count_result)['total'];

// จัดเรียงคอมเมนต์เป็นโครงสร้าง parent-child
$comments = [];
$replies = [];
if ($comments_result) {
    while ($comment = mysqli_fetch_assoc($comments_result)) {
        if ($comment['parent_comment_id'] === NULL) {
            $comments[$comment['comment_id']] = $comment;
            $comments[$comment['comment_id']]['replies'] = [];
        } else {
            $replies[$comment['parent_comment_id']][] = $comment;
        }
    }
    // รวม replies เข้ากับ parent comments
    foreach ($replies as $parent_id => $reply_list) {
        if (isset($comments[$parent_id])) {
            $comments[$parent_id]['replies'] = $reply_list;
        }
    }
}

// ดึงบทความที่เกี่ยวข้อง (category เดียวกัน)
$related_sql = "SELECT * FROM articles
                WHERE category = '{$article['category']}'
                AND article_id != '$article_id'
                AND status = 'published'
                ORDER BY published_at DESC
                LIMIT 3";
$related_articles = mysqli_query($conn, $related_sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - Green Digital</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Article Header -->
    <section class="bg-success bg-gradient text-white py-5 mb-4">
        <div class="container">
            <?php if(!empty($article['category'])): ?>
                <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-2 mb-3">
                    <?php echo htmlspecialchars($article['category']); ?>
                </span>
            <?php endif; ?>

            <h1 class="display-3 fw-bold mb-3"><?php echo htmlspecialchars($article['title']); ?></h1>

            <div class="fs-5 opacity-75">
                📅 <?php echo date('d F Y', strtotime($article['published_at'])); ?>
                <?php if(!empty($article['author_name'])): ?>
                    | ✍️ <?php echo htmlspecialchars($article['author_name']); ?>
                <?php endif; ?>
                | 👁️ <?php echo number_format($article['views']); ?> ครั้ง
            </div>
        </div>
    </section>

    <!-- Article Content -->
    <div class="container mb-5">
        <div class="mb-3">
            <a href="articles.php" class="btn btn-outline-success shadow-sm">← กลับไปหน้าบทความ</a>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if(!empty($article['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars(ltrim($article['image_url'], '/')); ?>"
                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                         class="img-fluid rounded shadow-sm mb-4"
                         style="max-height: 500px; width: 100%; object-fit: cover;">
                <?php endif; ?>

                <div class="fs-5 lh-lg text-dark mb-4">
                    <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                </div>

                <hr class="my-4">

                <div class="text-center my-4">
                    <a href="articles.php" class="btn btn-success shadow-sm">อ่านบทความอื่นๆ</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <section class="bg-light py-5" id="comments">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h3 class="display-6 fw-bold mb-4 text-dark">💬 ความคิดเห็น (<?php echo $comment_count; ?>)</h3>

                    <?php if(isset($_GET['comment_success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            ✅ ส่งความคิดเห็นเรียบร้อยแล้ว!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['edit_success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            ✅ แก้ไขความคิดเห็นเรียบร้อยแล้ว!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['delete_success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            ✅ ลบความคิดเห็นเรียบร้อยแล้ว!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            ❌
                            <?php
                            switch($_GET['error']) {
                                case 'empty': echo 'กรุณากรอกความคิดเห็น'; break;
                                case 'too_long': echo 'ความคิดเห็นยาวเกินไป (สูงสุด 2000 ตัวอักษร)'; break;
                                case 'time_expired': echo 'หมดเวลาแก้ไข (สามารถแก้ไขได้ภายใน 15 นาที)'; break;
                                default: echo 'เกิดข้อผิดพลาด';
                            }
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- แสดงคอมเมนต์ทั้งหมด -->
                    <?php if(count($comments) > 0): ?>
                        <?php foreach($comments as $comment): ?>
                            <div class="card border-0 shadow-sm mb-3" id="comment-<?php echo $comment['comment_id']; ?>">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="fw-bold text-success fs-6">
                                                <?php if($comment['admin_id']): ?>
                                                    👨‍💼 <?php echo htmlspecialchars($comment['admin_name']); ?> <span class="badge bg-primary">Admin</span>
                                                <?php else: ?>
                                                    👤 <?php echo htmlspecialchars($comment['user_first_name'] . ' ' . $comment['user_last_name']); ?>
                                                <?php endif; ?>
                                            </span>
                                            <span class="text-muted small">
                                                | <?php
                                                    $time_ago = time() - strtotime($comment['created_at']);
                                                    if ($time_ago < 60) echo 'เมื่อสักครู่';
                                                    elseif ($time_ago < 3600) echo floor($time_ago / 60) . ' นาทีที่แล้ว';
                                                    elseif ($time_ago < 86400) echo floor($time_ago / 3600) . ' ชั่วโมงที่แล้ว';
                                                    else echo floor($time_ago / 86400) . ' วันที่แล้ว';
                                                ?>
                                            </span>
                                            <?php if($comment['updated_at']): ?>
                                                <span class="text-muted small fst-italic">(แก้ไขแล้ว)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="text-dark lh-base mb-3">
                                        <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="toggleReplyForm(<?php echo $comment['comment_id']; ?>)">
                                            💬 ตอบกลับ
                                        </button>

                                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                                        <?php
                                        $can_edit = (time() - strtotime($comment['created_at'])) <= (15 * 60);
                                        if($can_edit):
                                        ?>
                                            <button class="btn btn-sm btn-outline-warning" onclick="toggleEditForm(<?php echo $comment['comment_id']; ?>)">
                                                ✏️ แก้ไข
                                            </button>
                                        <?php endif; ?>

                                        <form method="POST" action="sql/comment_delete.php" style="display: inline;" onsubmit="return confirm('ยืนยันการลบความคิดเห็น?')">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">🗑️ ลบ</button>
                                        </form>
                                    <?php endif; ?>
                                    </div>

                                    <!-- ฟอร์มแก้ไข (ซ่อนไว้) -->
                                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id'] && $can_edit): ?>
                                        <div class="mt-3 p-3 bg-light rounded d-none" id="edit-form-<?php echo $comment['comment_id']; ?>">
                                            <form method="POST" action="sql/comment_edit.php">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                                <textarea name="comment_text" class="form-control mb-2" rows="3" required><?php echo htmlspecialchars($comment['comment_text']); ?></textarea>
                                                <button type="submit" class="btn btn-sm btn-warning">💾 บันทึก</button>
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleEditForm(<?php echo $comment['comment_id']; ?>)">ยกเลิก</button>
                                                <small class="text-muted d-block mt-1">แก้ไขได้ภายใน 15 นาทีหลังโพสต์</small>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <!-- ฟอร์มตอบกลับ (ซ่อนไว้) -->
                                    <div class="mt-3 p-3 bg-light rounded d-none" id="reply-form-<?php echo $comment['comment_id']; ?>">
                                        <?php if(isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])): ?>
                                            <form method="POST" action="sql/comment_add.php">
                                                <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                                                <input type="hidden" name="parent_comment_id" value="<?php echo $comment['comment_id']; ?>">
                                                <textarea name="comment_text" class="form-control mb-2" placeholder="เขียนตอบกลับ..." rows="3" required></textarea>
                                                <button type="submit" class="btn btn-sm btn-primary">ส่งตอบกลับ</button>
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleReplyForm(<?php echo $comment['comment_id']; ?>)">ยกเลิก</button>
                                            </form>
                                        <?php else: ?>
                                            <div class="alert alert-info mb-0">
                                                กรุณา <a href="user_login.php">เข้าสู่ระบบ</a> เพื่อตอบกลับความคิดเห็น
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- แสดงคำตอบกลับ (Replies) -->
                                    <?php if(count($comment['replies']) > 0): ?>
                                        <?php foreach($comment['replies'] as $reply): ?>
                                            <div class="ms-5 mt-3 ps-3 border-start border-success border-3" id="comment-<?php echo $reply['comment_id']; ?>">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div>
                                                        <span class="fw-bold text-success fs-6">
                                                            <?php if($reply['admin_id']): ?>
                                                                👨‍💼 <?php echo htmlspecialchars($reply['admin_name']); ?> <span class="badge bg-primary">Admin</span>
                                                            <?php else: ?>
                                                                👤 <?php echo htmlspecialchars($reply['user_first_name'] . ' ' . $reply['user_last_name']); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="text-muted small">
                                                            | <?php
                                                                $time_ago = time() - strtotime($reply['created_at']);
                                                                if ($time_ago < 60) echo 'เมื่อสักครู่';
                                                                elseif ($time_ago < 3600) echo floor($time_ago / 60) . ' นาทีที่แล้ว';
                                                                elseif ($time_ago < 86400) echo floor($time_ago / 3600) . ' ชั่วโมงที่แล้ว';
                                                                else echo floor($time_ago / 86400) . ' วันที่แล้ว';
                                                            ?>
                                                        </span>
                                                        <?php if($reply['updated_at']): ?>
                                                            <span class="text-muted small fst-italic">(แก้ไขแล้ว)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="text-dark lh-base mb-3">
                                                    <?php echo nl2br(htmlspecialchars($reply['comment_text'])); ?>
                                                </div>

                                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $reply['user_id']): ?>
                                                    <div class="d-flex gap-2 mb-2">
                                                        <?php
                                                        $can_edit_reply = (time() - strtotime($reply['created_at'])) <= (15 * 60);
                                                        if($can_edit_reply):
                                                        ?>
                                                            <button class="btn btn-sm btn-outline-warning" onclick="toggleEditForm(<?php echo $reply['comment_id']; ?>)">
                                                                ✏️ แก้ไข
                                                            </button>
                                                        <?php endif; ?>

                                                        <form method="POST" action="sql/comment_delete.php" class="d-inline" onsubmit="return confirm('ยืนยันการลบความคิดเห็น?')">
                                                            <input type="hidden" name="comment_id" value="<?php echo $reply['comment_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">🗑️ ลบ</button>
                                                        </form>
                                                    </div>

                                                    <?php if($can_edit_reply): ?>
                                                        <div class="p-3 bg-light rounded d-none mt-2" id="edit-form-<?php echo $reply['comment_id']; ?>">
                                                            <form method="POST" action="sql/comment_edit.php">
                                                                <input type="hidden" name="comment_id" value="<?php echo $reply['comment_id']; ?>">
                                                                <textarea name="comment_text" class="form-control mb-2" rows="2" required><?php echo htmlspecialchars($reply['comment_text']); ?></textarea>
                                                                <button type="submit" class="btn btn-sm btn-warning">💾 บันทึก</button>
                                                                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleEditForm(<?php echo $reply['comment_id']; ?>)">ยกเลิก</button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            💭 ยังไม่มีความคิดเห็น เป็นคนแรกที่แสดงความคิดเห็นสิ!
                        </div>
                    <?php endif; ?>

                    <!-- ฟอร์มเขียนความคิดเห็นใหม่ -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-body p-4">
                            <h5 class="mb-3 fw-bold">✍️ แสดงความคิดเห็น</h5>
                            <?php if(isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])): ?>
                                <?php if(isset($_SESSION['admin_id'])): ?>
                                    <div class="alert alert-info mb-3">
                                        <strong>👨‍💼 คุณกำลังคอมเมนต์ในฐานะ Admin:</strong> <?php echo $_SESSION['full_name']; ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" action="sql/comment_add.php">
                                    <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                                    <textarea name="comment_text" class="form-control mb-3" placeholder="แชร์ความคิดเห็นของคุณ..." rows="4" maxlength="2000" required style="resize: vertical; min-height: 120px;"></textarea>
                                    <small class="text-muted d-block mb-2">สูงสุด 2000 ตัวอักษร</small>
                                    <button type="submit" class="btn btn-success shadow-sm">📨 ส่งความคิดเห็น</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    กรุณา <a href="user_login.php" class="alert-link">เข้าสู่ระบบ</a> หรือ <a href="user_register.php" class="alert-link">สมัครสมาชิก</a> เพื่อแสดงความคิดเห็น
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Articles -->
    <?php if($related_articles && mysqli_num_rows($related_articles) > 0): ?>
    <section class="bg-light py-5 mt-5">
        <div class="container">
            <h3 class="text-center mb-4 fw-bold">📌 บทความที่เกี่ยวข้อง</h3>

            <div class="row g-4">
                <?php while($related = mysqli_fetch_assoc($related_articles)): ?>
                    <div class="col-md-4">
                        <a href="article_detail.php?id=<?php echo $related['article_id']; ?>" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100">
                                <?php if(!empty($related['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars(ltrim($related['image_url'], '/')); ?>"
                                         alt="<?php echo htmlspecialchars($related['title']); ?>"
                                         class="card-img-top"
                                         style="height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center bg-light" style="height: 150px;">
                                        <span class="fs-1">📄</span>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body p-3">
                                    <h5 class="fw-bold text-dark mb-2" style="overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </h5>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($related['published_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Reply Form
        function toggleReplyForm(commentId) {
            const form = document.getElementById('reply-form-' + commentId);
            form.classList.toggle('d-none');
        }

        // Toggle Edit Form
        function toggleEditForm(commentId) {
            const form = document.getElementById('edit-form-' + commentId);
            form.classList.toggle('d-none');
        }
    </script>
</body>
</html>
