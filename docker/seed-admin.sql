-- บัญชีผู้ดูแลระบบสำหรับทดสอบบนเครื่อง (Docker) เท่านั้น ห้ามนำไปใช้บนเซิร์ฟเวอร์จริง
-- เข้าสู่ระบบที่ http://localhost:8080/login.php
--   username: admin
--   password: admin1234
INSERT INTO `admins` (`username`, `password`, `full_name`, `email`, `role`, `status`)
VALUES ('admin', '$2y$10$ehb6lMPq/sR3gFMC7/C1Tu0YGLI6WgR5/pg01btzMEb7hf.tIDwdy', 'ผู้ดูแลระบบ (ทดสอบ)', 'admin@localhost', 'owner', 'active');
