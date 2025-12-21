<?php
require_once '../config.php';

// ตรวจสอบว่า login แล้ว
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $comment_id = intval($_POST['comment_id']);
    $comment_text = trim($_POST['comment_text']);

    // Validate
    if (empty($comment_text)) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=empty");
        exit();
    }

    // จำกัดความยาว 2000 ตัวอักษร
    if (mb_strlen($comment_text) > 2000) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=too_long");
        exit();
    }

    // ตรวจสอบว่าคอมเมนต์นี้เป็นของ user คนนี้หรือไม่
    $check_sql = "SELECT article_id, created_at FROM article_comments
                  WHERE comment_id = $comment_id AND user_id = $user_id AND status = 'active'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=not_found");
        exit();
    }

    $comment = mysqli_fetch_assoc($check_result);
    $article_id = $comment['article_id'];

    // ตรวจสอบเวลา - ให้แก้ไขได้ภายใน 15 นาที
    $created_time = strtotime($comment['created_at']);
    $current_time = time();
    $time_diff = $current_time - $created_time;

    if ($time_diff > (15 * 60)) { // 15 นาที
        header("Location: ../article_detail.php?id=$article_id&error=time_expired");
        exit();
    }

    // Escape ข้อมูล
    $comment_text = mysqli_real_escape_string($conn, $comment_text);

    // แก้ไขคอมเมนต์
    $sql = "UPDATE article_comments
            SET comment_text = '$comment_text', updated_at = NOW()
            WHERE comment_id = $comment_id AND user_id = $user_id";

    if (mysqli_query($conn, $sql)) {
        header("Location: ../article_detail.php?id=$article_id&edit_success=1#comment-$comment_id");
        exit();
    } else {
        header("Location: ../article_detail.php?id=$article_id&error=failed");
        exit();
    }
} else {
    header("Location: ../articles.php");
    exit();
}
?>
