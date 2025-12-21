<?php
require_once '../config.php';

echo "<h2>ทดสอบการดึงข้อมูล Notifications</h2>";

// ตรวจสอบว่ามีตาราง notifications หรือไม่
$check_table = $conn->query("SHOW TABLES LIKE 'notifications'");
echo "<h3>1. ตรวจสอบตาราง:</h3>";
if ($check_table && $check_table->num_rows > 0) {
    echo "✅ มีตาราง notifications<br>";
} else {
    echo "❌ ไม่มีตาราง notifications<br>";
    exit;
}

// ดูโครงสร้างตาราง
echo "<h3>2. โครงสร้างตาราง:</h3>";
$structure = $conn->query("DESCRIBE notifications");
if ($structure) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// นับจำนวนข้อมูลทั้งหมด
echo "<h3>3. จำนวนข้อมูลทั้งหมด:</h3>";
$count_all = $conn->query("SELECT COUNT(*) as total FROM notifications");
if ($count_all) {
    $total = $count_all->fetch_assoc()['total'];
    echo "ทั้งหมด: <strong>$total</strong> รายการ<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// นับจำนวนที่ user_id IS NULL
echo "<h3>4. จำนวนที่ user_id IS NULL (สำหรับ Admin):</h3>";
$count_admin = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE user_id IS NULL");
if ($count_admin) {
    $total_admin = $count_admin->fetch_assoc()['total'];
    echo "Admin notifications: <strong>$total_admin</strong> รายการ<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// ดูข้อมูลทั้งหมด
echo "<h3>5. ข้อมูลทั้งหมดในตาราง:</h3>";
$all_data = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
if ($all_data && $all_data->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>user_id</th><th>title</th><th>message</th><th>type</th><th>is_read</th><th>created_at</th></tr>";
    while($row = $all_data->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['notification_id'] . "</td>";
        echo "<td>" . ($row['user_id'] ?: 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['message'], 0, 50)) . "...</td>";
        echo "<td>" . $row['type'] . "</td>";
        echo "<td>" . $row['is_read'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "ไม่มีข้อมูล หรือ Error: " . $conn->error;
}

// ทดสอบ Query ที่ใช้ใน navbar
echo "<h3>6. ทดสอบ Query ที่ใช้ใน Navbar:</h3>";
$navbar_query = "SELECT * FROM notifications WHERE user_id IS NULL ORDER BY created_at DESC LIMIT 5";
echo "Query: <code>$navbar_query</code><br><br>";
$navbar_result = $conn->query($navbar_query);
if ($navbar_result) {
    echo "จำนวนผลลัพธ์: <strong>" . $navbar_result->num_rows . "</strong> รายการ<br>";
    if ($navbar_result->num_rows > 0) {
        echo "<ul>";
        while($row = $navbar_result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row['title']) . " - " . $row['created_at'] . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
