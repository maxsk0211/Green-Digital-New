<?php
session_start();

// ลบข้อมูล Session ทั้งหมด
session_destroy();

// กลับไปหน้า Login
header('Location: ../login.php');
exit();
?>
