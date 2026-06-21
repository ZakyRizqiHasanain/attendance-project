-- =========================================
-- TABLE USERS
-- =========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    photo VARCHAR(255) DEFAULT 'default.png',
    role ENUM('admin','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================
-- TABLE WORK SCHEDULE
-- =========================================
CREATE TABLE work_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_name ENUM(
        'Monday','Tuesday','Wednesday','Thursday',
        'Friday','Saturday','Sunday'
    ) NOT NULL UNIQUE,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL
);

-- =========================================
-- DEFAULT WORK SCHEDULE
-- =========================================
INSERT INTO work_schedule (day_name, start_time, end_time)
VALUES
('Monday','08:00:00','17:00:00'),
('Tuesday','08:00:00','17:00:00'),
('Wednesday','08:00:00','17:00:00'),
('Thursday','08:00:00','17:00:00'),
('Friday','08:00:00','17:00:00'),
('Saturday','08:00:00','12:00:00'),
('Sunday','00:00:00','00:00:00');

-- =========================================
-- TABLE ATTENDANCE
-- =========================================
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,

    attendance_date DATE NOT NULL,
    check_in DATETIME NULL,
    check_out DATETIME NULL,

    selfie VARCHAR(255) NULL,
    selfie_checkout VARCHAR(255) NULL,

    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,

    status ENUM(
        'Hadir',
        'Terlambat',
        'Alpha',
        'Izin',
        'Sakit'
    ) NOT NULL DEFAULT 'Alpha',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_attendance (user_id, attendance_date),

    INDEX idx_user_date (user_id, attendance_date),
    INDEX idx_status (status),
    INDEX idx_attendance_date (attendance_date),

    CONSTRAINT fk_attendance_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

-- =========================================
-- TABLE LEAVE REQUESTS
-- =========================================
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,

    leave_date DATE NOT NULL,
    type ENUM('Izin','Sakit') NOT NULL,
    reason TEXT NOT NULL,

    attachment VARCHAR(255) DEFAULT NULL,

    approval_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',

    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_leave_user_date (user_id, leave_date),
    INDEX idx_leave_status (approval_status),

    CONSTRAINT fk_leave_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_leave_admin
        FOREIGN KEY (approved_by) REFERENCES users(id)
        ON DELETE SET NULL
);

INSERT INTO users (name, email, password, role)
VALUES 
('Administrator', 'admin@gmail.com', '$2y$10$EXAMPLEHASHEDPASSWORD', 'admin'),
('User Demo', 'user@gmail.com', '$2y$10$EXAMPLEHASHEDPASSWORD', 'user');    