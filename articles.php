<?php
session_start();
require_once 'config.php';

// ตั้งค่าสำหรับ navbar
$base_url = '';
$current_page = 'articles';

// รับค่า category จาก URL
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// ดึงบทความที่ published และอยู่ในช่วงเวลาแสดงผล
$where_clause = "a.status = 'published'
                 AND NOW() >= a.published_start
                 AND (a.published_end IS NULL OR NOW() <= a.published_end)";

if (!empty($category_filter)) {
    $category_escaped = mysqli_real_escape_string($conn, $category_filter);
    $sql = "SELECT a.*, ad.full_name as author_name
            FROM articles a
            LEFT JOIN admins ad ON a.author_id = ad.admin_id
            WHERE $where_clause AND a.category = '$category_escaped'
            ORDER BY a.published_at DESC";
} else {
    $sql = "SELECT a.*, ad.full_name as author_name
            FROM articles a
            LEFT JOIN admins ad ON a.author_id = ad.admin_id
            WHERE $where_clause
            ORDER BY a.published_at DESC";
}

$articles_result = mysqli_query($conn, $sql);

// ดึง category ทั้งหมด (เฉพาะบทความที่แสดงผลได้)
$category_sql = "SELECT DISTINCT category
                 FROM articles
                 WHERE $where_clause AND category IS NOT NULL
                 ORDER BY category";
$categories = mysqli_query($conn, $category_sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บทความและความรู้ - Green Digital</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Page Header -->
    <section class="bg-success bg-gradient text-white py-5 mb-4">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">📚 บทความและความรู้</h1>
            <p class="fs-5 opacity-75 mb-0">เรียนรู้เกี่ยวกับการรีไซเคิล การคัดแยกขยะ และการดูแลสิ่งแวดล้อม</p>
        </div>
    </section>

    <div class="container mb-5">
        <!-- Category Filter -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3 fw-bold text-center">🏷️ หมวดหมู่</h5>
                <div class="text-center">
                    <a href="articles.php" class="btn btn-outline-success m-1 shadow-sm <?php echo empty($category_filter) ? 'active' : ''; ?>">
                        ทั้งหมด
                    </a>
                    <?php if($categories && mysqli_num_rows($categories) > 0): ?>
                        <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                            <a href="articles.php?category=<?php echo urlencode($cat['category']); ?>"
                               class="btn btn-outline-success m-1 shadow-sm <?php echo $category_filter == $cat['category'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Articles Grid -->
        <div class="row g-4">
            <?php if($articles_result && mysqli_num_rows($articles_result) > 0): ?>
                <?php while($article = mysqli_fetch_assoc($articles_result)): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <?php if(!empty($article['image_url'])): ?>
                                <img src="<?php echo $article['image_url']; ?>"
                                     alt="<?php echo $article['title']; ?>"
                                     class="card-img-top bg-light"
                                     style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                    <span class="display-1">📄</span>
                                </div>
                            <?php endif; ?>

                            <div class="card-body p-4 d-flex flex-column">
                                <?php if(!empty($article['category'])): ?>
                                    <span class="badge bg-success rounded-pill align-self-start mb-2">
                                        <?php echo htmlspecialchars($article['category']); ?>
                                    </span>
                                <?php endif; ?>

                                <h3 class="fs-5 fw-bold text-dark mb-2 text-truncate" title="<?php echo htmlspecialchars($article['title']); ?>">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </h3>

                                <p class="text-muted mb-3" style="overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                    <?php
                                    $content = strip_tags($article['content']);
                                    echo htmlspecialchars(mb_substr($content, 0, 150)) . '...';
                                    ?>
                                </p>

                                <div class="text-muted small mb-3">
                                    📅 <?php echo date('d/m/Y', strtotime($article['published_at'])); ?>
                                    <?php if(!empty($article['author_name'])): ?>
                                        | ✍️ <?php echo htmlspecialchars($article['author_name']); ?>
                                    <?php endif; ?>
                                    | 👁️ <?php echo number_format($article['views']); ?>
                                </div>

                                <a href="article_detail.php?id=<?php echo $article['article_id']; ?>"
                                   class="btn btn-success shadow-sm mt-auto">
                                    อ่านต่อ →
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-center p-5">
                        <div class="card-body">
                            <div class="display-1 mb-4">📭</div>
                            <h3 class="mb-3">ยังไม่มีบทความ</h3>
                            <p class="text-muted mb-0">กำลังเตรียมเนื้อหาที่น่าสนใจให้คุณ</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
