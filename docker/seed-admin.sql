-- บัญชีผู้ดูแลระบบสำหรับทดสอบบนเครื่อง (Docker) เท่านั้น ห้ามนำไปใช้บนเซิร์ฟเวอร์จริง
-- เข้าสู่ระบบที่ http://localhost:8080/login.php
--   username: admin
--   password: Zzmw8xPFFwqiiNsZ
INSERT INTO `admins` (`username`, `password`, `full_name`, `email`, `role`, `status`)
VALUES ('admin', '$2y$10$RrC12oiAO7j.9BUOa7qek.prrRzcg4hPu/YtgFB4ut0YJ.oNJsiqe', 'ผู้ดูแลระบบ (ทดสอบ)', 'admin@localhost', 'owner', 'active');
