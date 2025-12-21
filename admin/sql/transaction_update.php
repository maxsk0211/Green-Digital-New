<?php
require_once '../../config.php';

// ตรวจสอบว่าเป็น Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ตรวจสอบว่ามีการส่งข้อมูลมา
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $transaction_id = $_POST['transaction_id'];
    $status = $_POST['status'];

    // อัปเดตสถานะธุรกรรม
    $sql = "UPDATE transactions SET status = '$status' WHERE transaction_id = '$transaction_id'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: ../transactions.php?success=updated");
    } else {
        header("Location: ../transactions.php?error=failed");
    }
} else {
    header("Location: ../transactions.php");
}
?>
