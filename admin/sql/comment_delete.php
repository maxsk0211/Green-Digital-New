<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $comment_id = intval($_POST['comment_id']);

    // ลบคอมเมนต์ถาวร (ลบจริงจากฐานข้อมูล)
    // รวมถึงคอมเมนต์ย่อย (replies) ที่เชื่อมโยงด้วย (CASCADE จะจัดการให้)
    $sql = "DELETE FROM article_comments WHERE comment_id = $comment_id";

    if (mysqli_query($conn, $sql)) {
        header("Location: ../article_comments.php?success=1");
        exit();
    } else {
        header("Location: ../article_comments.php?error=1");
        exit();
    }
} else {
    header("Location: ../article_comments.php");
    exit();
}
?>
