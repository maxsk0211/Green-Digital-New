<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้ว (user หรือ admin)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: ../user_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ตรวจสอบว่าเป็น user หรือ admin
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : NULL;

    $article_id = intval($_POST['article_id']);
    $comment_text = trim($_POST['comment_text']);
    $parent_comment_id = isset($_POST['parent_comment_id']) && !empty($_POST['parent_comment_id'])
                         ? intval($_POST['parent_comment_id'])
                         : NULL;

    // Validate
    if (empty($comment_text)) {
        header("Location: ../article_detail.php?id=$article_id&error=empty");
        exit();
    }

    // จำกัดความยาว 2000 ตัวอักษร
    if (mb_strlen($comment_text) > 2000) {
        header("Location: ../article_detail.php?id=$article_id&error=too_long");
        exit();
    }

    // ตรวจสอบว่า article มีอยู่จริง
    $article_check = mysqli_query($conn, "SELECT article_id FROM articles WHERE article_id = $article_id");
    if (mysqli_num_rows($article_check) == 0) {
        header("Location: ../articles.php?error=article_not_found");
        exit();
    }

    // ตรวจสอบ parent_comment (ถ้ามี)
    if ($parent_comment_id !== NULL) {
        $parent_check = mysqli_query($conn, "SELECT comment_id FROM article_comments WHERE comment_id = $parent_comment_id AND article_id = $article_id");
        if (mysqli_num_rows($parent_check) == 0) {
            $parent_comment_id = NULL; // ถ้าไม่พบ parent ให้เป็น comment หลัก
        }
    }

    // Escape ข้อมูล
    $comment_text = mysqli_real_escape_string($conn, $comment_text);

    // เพิ่มคอมเมนต์ (แสดงทันที - status = active)
    // สร้าง SQL แยกตามว่าเป็น user หรือ admin
    if ($parent_comment_id === NULL) {
        if ($admin_id !== NULL) {
            // คอมเมนต์จาก admin
            $sql = "INSERT INTO article_comments (article_id, user_id, admin_id, comment_text, status, created_at)
                    VALUES ($article_id, NULL, $admin_id, '$comment_text', 'active', NOW())";
        } else {
            // คอมเมนต์จาก user
            $sql = "INSERT INTO article_comments (article_id, user_id, admin_id, comment_text, status, created_at)
                    VALUES ($article_id, $user_id, NULL, '$comment_text', 'active', NOW())";
        }
    } else {
        if ($admin_id !== NULL) {
            // Reply จาก admin
            $sql = "INSERT INTO article_comments (article_id, user_id, admin_id, comment_text, parent_comment_id, status, created_at)
                    VALUES ($article_id, NULL, $admin_id, '$comment_text', $parent_comment_id, 'active', NOW())";
        } else {
            // Reply จาก user
            $sql = "INSERT INTO article_comments (article_id, user_id, admin_id, comment_text, parent_comment_id, status, created_at)
                    VALUES ($article_id, $user_id, NULL, '$comment_text', $parent_comment_id, 'active', NOW())";
        }
    }

    if (mysqli_query($conn, $sql)) {
        // สำเร็จ
        header("Location: ../article_detail.php?id=$article_id&comment_success=1#comments");
        exit();
    } else {
        // ล้มเหลว
        header("Location: ../article_detail.php?id=$article_id&error=failed");
        exit();
    }
} else {
    header("Location: ../articles.php");
    exit();
}
?>
