-- ตารางผู้ดูแลระบบ
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ตารางห้องประชุม
CREATE TABLE IF NOT EXISTS rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  location VARCHAR(100) DEFAULT NULL,
  display_order INT NOT NULL DEFAULT 0, -- สำหรับจัดลำดับการแสดงผล
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ตารางกำหนดการ/หัวข้อในแต่ละห้อง
CREATE TABLE IF NOT EXISTS room_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  topic VARCHAR(255) NOT NULL,
  speaker VARCHAR(150) DEFAULT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME NOT NULL,
  status ENUM('upcoming','live','done') DEFAULT 'upcoming',
  is_current TINYINT(1) DEFAULT 0,  -- 1 = เป็นหัวข้อที่กำลังบรรยาย
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ผู้ดูแลระบบเริ่มต้น (username: admin / password: admin1234)
INSERT INTO admins (username, password_hash, name)
VALUES ('admin', '$2y$10$3Q5M1d2F3Ac0N7o0p3o7rO4r6L0w0Qe1x1xH8uB4i1Q.6b4k4dQxC', 'Administrator');
-- หมายเหตุ: hash นี้ของ "admin1234" (ตัวอย่าง) คุณควรเปลี่ยนรหัสผ่านจริง

-- ห้องตัวอย่าง
INSERT INTO rooms (name, location, display_order) VALUES
('ห้อง A', 'ชั้น 2', 1),
('ห้อง B', 'ชั้น 2', 2),
('ห้อง C', 'ชั้น 3', 3);

-- ตารางกำหนดการตัวอย่าง (เวลาตัวอย่างวันนี้)
-- ปรับเวลาให้ตรง timezone ของเครื่อง
INSERT INTO room_sessions (room_id, topic, speaker, start_time, end_time, status, is_current)
VALUES
(1, 'AI เบื้องต้น', 'ดร. กิตติ', NOW(), DATE_ADD(NOW(), INTERVAL 60 MINUTE), 'live', 1),
(1, 'GenAI ในงานเอกสาร', 'คุณมีนา', DATE_ADD(NOW(), INTERVAL 70 MINUTE), DATE_ADD(NOW(), INTERVAL 130 MINUTE), 'upcoming', 0),
(2, 'Cybersecurity 101', 'คุณพงษ์', DATE_ADD(NOW(), INTERVAL -30 MINUTE), DATE_ADD(NOW(), INTERVAL 30 MINUTE), 'live', 1),
(3, 'Cloud Cost Saving', 'คุณบี', DATE_ADD(NOW(), INTERVAL 30 MINUTE), DATE_ADD(NOW(), INTERVAL 90 MINUTE), 'upcoming', 0);
