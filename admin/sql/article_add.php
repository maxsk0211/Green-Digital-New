<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ตรวจสอบว่ามีการส่งข้อมูลมา
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $content = $_POST['content'];
    $status = $_POST['status'];
    $author_id = $_SESSION['admin_id'];
    $image_url = NULL;

    // รับค่าวันเวลาเปิด-ปิด
    $published_start = null;
    $published_end = null;

    if ($status == 'published') {
        $published_start = !empty($_POST['published_start']) ? $_POST['published_start'] : date('Y-m-d H:i:s');
        $published_end = !empty($_POST['published_end']) ? $_POST['published_end'] : null;
    }

    // จัดการอัปโหลดรูปภาพ
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filesize = $_FILES['image']['size'];

        // ตรวจสอบไฟล์
        if (in_array($filetype, $allowed) && $filesize <= 5242880) { // 5MB
            // สร้างชื่อไฟล์ใหม่
            $newname = 'article_' . time() . '_' . uniqid() . '.' . $filetype;

            // อัปโหลดไฟล์
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path . $newname)) {
                $image_url = "uploads/articles/$newname";
            } else {
                header("Location: ../articles.php?error=upload_failed");
                exit();
            }
        } else {
            header("Location: ../articles.php?error=upload_failed");
            exit();
        }
    }

    // เพิ่มบทความ
    // ตั้งค่า published_at ถ้า status เป็น published
    $published_at = ($status == 'published') ? "NOW()" : "NULL";

    // สร้าง SQL query
    $published_start_sql = $published_start ? "'$published_start'" : "NOW()";
    $published_end_sql = $published_end ? "'$published_end'" : "NULL";

    if ($image_url) {
        $sql = "INSERT INTO articles (title, category, content, image_url, status, author_id, published_at, published_start, published_end)
                VALUES ('$title', '$category', '$content', '$image_url', '$status', '$author_id', $published_at, $published_start_sql, $published_end_sql)";
    } else {
        $sql = "INSERT INTO articles (title, category, content, status, author_id, published_at, published_start, published_end)
                VALUES ('$title', '$category', '$content', '$status', '$author_id', $published_at, $published_start_sql, $published_end_sql)";
    }

    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../articles.php?success=added");
    } else {
        // Debug: แสดง error
        // echo "Error: " . mysqli_error($conn);
        header("Location: ../articles.php?error=failed");
    }
} else {
    header("Location: ../articles.php");
}