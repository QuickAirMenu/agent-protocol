-- Zoe Assistant — Unified Schema
-- Run: mysql -u user -p dbname < schema.sql

CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    telegram_id     BIGINT UNIQUE,
    username        VARCHAR(100) UNIQUE,
    password_hash   VARCHAR(255),
    full_name       VARCHAR(200),
    role            ENUM('admin','user','viewer') DEFAULT 'user',
    is_active       TINYINT(1) DEFAULT 1,
    bot_state       VARCHAR(100) DEFAULT 'idle',
    bot_state_data  TEXT,
    last_login      DATETIME,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tasks (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    title           VARCHAR(500) NOT NULL,
    description     TEXT,
    priority        ENUM('low','medium','high','urgent') DEFAULT 'medium',
    status          ENUM('pending','in_progress','done','cancelled') DEFAULT 'pending',
    due_date        DATETIME,
    completed_at    DATETIME,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reminders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    message         TEXT NOT NULL,
    remind_at       DATETIME NOT NULL,
    sent            TINYINT(1) DEFAULT 0,
    sent_at         DATETIME,
    recurring       ENUM('none','daily','weekly','monthly') DEFAULT 'none',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pending (sent, remind_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matches (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    competition     VARCHAR(200),
    team_home       VARCHAR(200) NOT NULL,
    team_away       VARCHAR(200) NOT NULL,
    team_home_flag  VARCHAR(10),
    team_away_flag  VARCHAR(10),
    match_date      DATETIME,
    score_home      TINYINT UNSIGNED,
    score_away      TINYINT UNSIGNED,
    status          ENUM('scheduled','live','finished') DEFAULT 'scheduled',
    stage           VARCHAR(100),
    venue           VARCHAR(200),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date_status (match_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_history (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    role            ENUM('user','assistant') NOT NULL,
    content         TEXT NOT NULL,
    tokens_used     INT DEFAULT 0,
    source          ENUM('telegram','dashboard') DEFAULT 'telegram',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
