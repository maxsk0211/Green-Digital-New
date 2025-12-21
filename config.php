<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$host = "localhost";
$username = "chanchal_green_digital";
$password = "DSwgU7hpfk4QJEGw6Ksj";
$database = "chanchal_green_digital";

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($host, $username, $password, $database);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $conn->connect_error);
}

// ตั้งค่า charset เป็น UTF-8
$conn->set_charset("utf8mb4");

// เริ่ม session
session_start();
?>