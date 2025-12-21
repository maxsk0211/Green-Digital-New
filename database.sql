-- ===================================================================
-- Database: Green Digital - แพลตฟอร์มรับซื้อขยะรีไซเคิลถึงที่
-- ===================================================================
-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS green_digital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE green_digital;

-- ===================================================================
-- 1. ตาราง users - ข้อมูลสมาชิก/ผู้ใช้ระบบ
-- ===================================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    province VARCHAR(50),
    district VARCHAR(50),
    user_level ENUM('Bronze', 'Silver', 'Gold') DEFAULT 'Bronze',
    total_points INT DEFAULT 0,
    status ENUM('active', 'suspended') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 2. ตาราง admins - ข้อมูลผู้ดูแลระบบ
-- ===================================================================
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'owner', 'staff') DEFAULT 'staff',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 3. ตาราง recycle_types - ประเภทขยะรีไซเคิล
-- ===================================================================
CREATE TABLE recycle_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL COMMENT 'plastic, paper, metal, glass',
    description TEXT,
    image_url VARCHAR(255),
    co2_reduction DECIMAL(5,2) DEFAULT 0.00 COMMENT 'CO2 ที่ลดได้ต่อ 1 kg',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 4. ตาราง prices - ราคารับซื้อแต่ละประเภท
-- ===================================================================
CREATE TABLE prices (
    price_id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    price_per_kg DECIMAL(10,2) NOT NULL,
    effective_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT TRUE,
    updated_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES recycle_types(type_id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES admins(admin_id) ON DELETE SET NULL,
    INDEX idx_type_id (type_id),
    INDEX idx_is_current (is_current),
    INDEX idx_effective_date (effective_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 5. ตาราง bookings - การจองรับซื้อขยะ
-- ===================================================================
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    pickup_address TEXT NOT NULL,
    estimated_weight DECIMAL(10,2),
    estimated_price DECIMAL(10,2),
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    assigned_to INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES admins(admin_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_booking_date (booking_date),
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 6. ตาราง booking_items - รายการขยะในแต่ละการจอง
-- ===================================================================
CREATE TABLE booking_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    type_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL COMMENT 'น้ำหนัก (kg)',
    price_per_kg DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) AS (quantity * price_per_kg) STORED,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (type_id) REFERENCES recycle_types(type_id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id),
    INDEX idx_type_id (type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 7. ตาราง transactions - ธุรกรรมการจ่ายเงิน
-- ===================================================================
CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    total_weight DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'promptpay') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_date DATETIME,
    receipt_url VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id),
    INDEX idx_user_id (user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 8. ตาราง user_points - ประวัติการได้รับแต้มสะสม
-- ===================================================================
CREATE TABLE user_points (
    point_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT,
    points_earned INT DEFAULT 0,
    points_used INT DEFAULT 0,
    balance INT NOT NULL,
    description VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_booking_id (booking_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 9. ตาราง carbon_footprint - บันทึก CO2 ที่ช่วยลดได้ (Green Digital)
-- ===================================================================
CREATE TABLE carbon_footprint (
    footprint_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    co2_reduced DECIMAL(10,2) NOT NULL COMMENT 'CO2 ที่ลดได้ (kg)',
    trees_equivalent DECIMAL(10,2) NOT NULL COMMENT 'เทียบเท่าต้นไม้ (ต้น)',
    energy_saved DECIMAL(10,2) NOT NULL COMMENT 'พลังงานที่ประหยัด (kWh)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_booking_id (booking_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 10. ตาราง articles - บทความ/ความรู้
-- ===================================================================
CREATE TABLE articles (
    article_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(50),
    image_url VARCHAR(255),
    author_id INT,
    views INT DEFAULT 0,
    status ENUM('draft', 'published') DEFAULT 'draft',
    published_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES admins(admin_id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at),
    INDEX idx_views (views)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 11. ตาราง notifications - การแจ้งเตือน
-- ===================================================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT COMMENT 'NULL = แจ้งเตือนสำหรับ admin',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('booking', 'payment', 'system', 'promotion') DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 12. ตาราง promotions - โปรโมชั่น/ส่วนลด
-- ===================================================================
CREATE TABLE promotions (
    promotion_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    discount_type ENUM('percent', 'fixed') DEFAULT 'percent',
    discount_value DECIMAL(10,2) NOT NULL,
    min_purchase DECIMAL(10,2) DEFAULT 0.00,
    max_discount DECIMAL(10,2),
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    usage_limit INT,
    used_count INT DEFAULT 0,
    status ENUM('active', 'expired', 'disabled') DEFAULT 'active',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 13. ตาราง user_promotions - ประวัติการใช้โปรโมชั่นของผู้ใช้
-- ===================================================================
CREATE TABLE user_promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    promotion_id INT NOT NULL,
    booking_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (promotion_id) REFERENCES promotions(promotion_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_promotion_id (promotion_id),
    INDEX idx_booking_id (booking_id),
    INDEX idx_used_at (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 14. ตาราง reviews - รีวิว/ความคิดเห็นจากลูกค้า
-- ===================================================================
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL COMMENT 'คะแนน 1-5 ดาว',
    service_rating TINYINT COMMENT 'คะแนนบริการ 1-5',
    speed_rating TINYINT COMMENT 'คะแนนความรวดเร็ว 1-5',
    price_rating TINYINT COMMENT 'คะแนนความคุ้มค่า 1-5',
    comment TEXT,
    images TEXT COMMENT 'รูปภาพรีวิว (JSON array)',
    response TEXT COMMENT 'คำตอบจากผู้ดูแล',
    responded_by INT,
    responded_at DATETIME,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    is_featured BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES admins(admin_id) ON DELETE SET NULL,
    INDEX idx_booking_id (booking_id),
    INDEX idx_user_id (user_id),
    INDEX idx_rating (rating),
    INDEX idx_status (status),
    INDEX idx_is_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- ตัวอย่างข้อมูลเริ่มต้น (Sample Data)
-- ===================================================================

-- Admin ตัวอย่าง (รหัสผ่าน: admin123 - ต้องเข้ารหัสในระบบจริง)
INSERT INTO admins (username, password, full_name, email, role) VALUES
('admin', '$2y$10$YourHashedPasswordHere', 'ผู้ดูแลระบบ', 'admin@greendigital.com', 'admin');

-- ประเภทขยะตัวอย่าง
INSERT INTO recycle_types (type_name, category, description, co2_reduction, status) VALUES
('ขวดพลาสติก PET', 'plastic', 'ขวดน้ำดื่ม น้ำอัดลม', 2.50, 'active'),
('กระดาษ A4', 'paper', 'กระดาษสำนักงาน หนังสือพิมพ์', 1.80, 'active'),
('กระป๋องอลูมิเนียม', 'metal', 'กระป๋องเครื่องดื่ม', 8.00, 'active'),
('ขวดแก้ว', 'glass', 'ขวดแก้วน้ำดื่ม น้ำผลไม้', 0.60, 'active'),
('ถุงพลาสติก HDPE', 'plastic', 'ถุงพลาสติกหูหิ้ว', 2.00, 'active');

-- ราคาร���บซื้อตัวอย่าง
INSERT INTO prices (type_id, price_per_kg, effective_date, is_current) VALUES
(1, 8.00, CURDATE(), TRUE),
(2, 3.00, CURDATE(), TRUE),
(3, 45.00, CURDATE(), TRUE),
(4, 2.00, CURDATE(), TRUE),
(5, 5.00, CURDATE(), TRUE);

-- ===================================================================
-- สิ้นสุดการสร้างฐานข้อมูล
-- ===================================================================
