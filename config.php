<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$host = "localhost";
$username = "chanchal_green_digital";
$password = "";
$database = "chanchal_green_digital";

// รหัสผ่านของเซิร์ฟเวอร์จริงเก็บไว้ใน config.local.php ซึ่งไม่อยู่ใน git
// (ดูตัวอย่างใน config.local.example.php)
if (file_exists(__DIR__ . "/config.local.php")) {
    require __DIR__ . "/config.local.php";
}

// ถ้ามี environment variable จาก Docker จะใช้ค่านั้นแทน
$host = getenv("DB_HOST") ?: $host;
$username = getenv("DB_USER") ?: $username;
$password = getenv("DB_PASSWORD") ?: $password;
$database = getenv("DB_NAME") ?: $database;

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