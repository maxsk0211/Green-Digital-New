<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $article_id = $_GET['id'];

    // ดึงข้อมูลบทความเพื่อลบรูปภาพ
    $check_sql = "SELECT image_url FROM articles WHERE article_id = '$article_id'";
    $check_result = mysqli_query($conn, $check_sql);
    $article = mysqli_fetch_assoc($check_result);

    // ลบรูปภาพ
    if ($article && $article['image_url']) {
        $image_path = '../../' . $article['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // ลบบทความ
    $sql = "DELETE FROM articles WHERE article_id = '$article_id'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../articles.php?success=deleted");
    } else {
        header("Location: ../articles.php?error=failed");
    }
} else {
    header("Location: ../articles.php");
}
?>
