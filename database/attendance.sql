-- =========================================
-- TABLE USERS
-- =========================================
CREATE TABLE users (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(100) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    photo VARCHAR(255) NULL,

    role ENUM('admin','user') DEFAULT 'user',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- =========================================
-- TABLE ATTENDANCE
-- =========================================
CREATE TABLE attendance (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    check_in DATETIME NULL,

    check_out DATETIME NULL,

    selfie VARCHAR(255) NULL,

    selfie_checkout VARCHAR(255) NULL,

    latitude VARCHAR(50) NULL,

    longitude VARCHAR(50) NULL,

    status VARCHAR(50) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE

);

-- =========================================
-- DEFAULT ADMIN ACCOUNT
-- =========================================
INSERT INTO users (

    name,
    email,
    password,
    role

)

VALUES (

    'Administrator',

    'admin@gmail.com',

    'password',

    'admin'

);

-- =========================================
-- OPTIONAL SAMPLE USER
-- =========================================
INSERT INTO users (

    name,
    email,
    password,
    role

)

VALUES (

    'User Demo',

    'user@gmail.com',

    'password',

    'user'

);