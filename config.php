<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล (ถ้ามี environment variable จาก Docker จะใช้ค่านั้นแทน)
$host = getenv("DB_HOST") ?: "localhost";
$username = getenv("DB_USER") ?: "chanchal_green_digital";
$password = getenv("DB_PASSWORD") ?: "DSwgU7hpfk4QJEGw6Ksj";
$database = getenv("DB_NAME") ?: "chanchal_green_digital";

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