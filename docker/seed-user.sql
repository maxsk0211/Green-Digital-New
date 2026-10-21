-- บัญชีสมาชิกสำหรับทดสอบบนเครื่อง (Docker) เท่านั้น ห้ามนำไปใช้บนเซิร์ฟเวอร์จริง
-- เข้าสู่ระบบที่ http://localhost:8080/user_login.php (ใช้อีเมลหรือเบอร์โทรก็ได้)
--   email: member@example.com
--   phone: 0800000000
--   password: H472gZ6Pr9Bv
INSERT INTO `users` (`email`, `password`, `first_name`, `last_name`, `phone`, `address`, `province`, `district`, `security_answer`, `user_level`, `total_points`, `status`)
VALUES ('member@example.com', '$2y$10$uHtPgvZP2lChGO5Irgt6Guw9Qa2G96ExAkaycxUirqajqnP809cYa', 'สมาชิก', 'ทดสอบ', '0800000000', '1 ถนนทดสอบ', 'กรุงเทพมหานคร', 'บางรัก', 'ทดสอบ', 'Bronze', 0, 'active');
