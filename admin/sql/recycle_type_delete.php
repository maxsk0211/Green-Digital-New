<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $type_id = $_GET['id'];

    // ลบประเภทขยะ
    $sql = "DELETE FROM recycle_types WHERE type_id = '$type_id'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../recycle_types.php?success=deleted");
    } else {
        header("Location: ../recycle_types.php?error=failed");
    }
} else {
    header("Location: ../recycle_types.php");
}
?>
