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

    // ตรวจสอบว่าคอมเมนต์นี้เป็นของ user คนนี้หรือไม่
    $check_sql = "SELECT article_id FROM article_comments
                  WHERE comment_id = $comment_id AND user_id = $user_id AND status = 'active'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=not_found");
        exit();
    }

    $comment = mysqli_fetch_assoc($check_result);
    $article_id = $comment['article_id'];

    // ลบคอมเมนต์ (เปลี่ยน status เป็น deleted แทนการลบจริง)
    $sql = "UPDATE article_comments
            SET status = 'deleted'
            WHERE comment_id = $comment_id AND user_id = $user_id";

    if (mysqli_query($conn, $sql)) {
        header("Location: ../article_detail.php?id=$article_id&delete_success=1#comments");
        exit();
    } else {
        header("Location: ../article_detail.php?id=$article_id&error=delete_failed");
        exit();
    }
} else {
    header("Location: ../articles.php");
    exit();
}
?>
