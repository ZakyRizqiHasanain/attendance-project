-- =========================================
-- CREATE DATABASE
-- =========================================
CREATE DATABASE IF NOT EXISTS attendance_system;
USE attendance_system;

-- =========================================
-- TABLE USERS
-- =========================================
CREATE TABLE users (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(100) NOT NULL UNIQUE,

    password VARCHAR(100) NOT NULL,

    photo VARCHAR(255) DEFAULT 'default.png',

    role ENUM('admin','user') DEFAULT 'user',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- =========================================
-- TABLE ATTENDANCE
-- =========================================
CREATE TABLE attendance (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    attendance_date DATE NOT NULL,

    check_in TIME DEFAULT NULL,

    check_out TIME DEFAULT NULL,

    selfie VARCHAR(255) DEFAULT NULL,

    selfie_checkout VARCHAR(255) DEFAULT NULL,

    latitude DECIMAL(10,8) DEFAULT NULL,

    longitude DECIMAL(11,8) DEFAULT NULL,

    status ENUM(
        'Hadir',
        'Terlambat',
        'Izin',
        'Sakit'
    ) DEFAULT 'Hadir',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),

    UNIQUE KEY unique_attendance (
        user_id,
        attendance_date
    ),

    CONSTRAINT fk_attendance_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE

);

-- =========================================
-- DEFAULT ADMIN
-- =========================================
INSERT INTO users (

    name,
    email,
    password,
    role

) VALUES (

    'Administrator',
    'admin@gmail.com',
    'admin123',
    'admin'

);

-- =========================================
-- DEMO USER
-- =========================================
INSERT INTO users (

    name,
    email,
    password,
    role

) VALUES (

    'User Demo',
    'user@gmail.com',
    'user123',
    'user'

);