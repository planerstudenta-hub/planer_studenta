CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL,
    INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(160) NOT NULL,
    type ENUM('egzamin', 'projekt', 'zajecia', 'praktyki', 'wyjazd', 'sluzba', 'inne') NOT NULL DEFAULT 'inne',
    start_at DATETIME NOT NULL,
    end_at DATETIME NULL,
    location VARCHAR(180) NULL,
    notes TEXT NULL,
    reminder_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    prep_note VARCHAR(255) NULL,
    prep_reminder_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    recurrence ENUM('none', 'daily', 'weekly', 'monthly') NOT NULL DEFAULT 'none',
    recurrence_until DATE NULL,
    status ENUM('planned', 'done') NOT NULL DEFAULT 'planned',
    visibility ENUM('private', 'friends') NOT NULL DEFAULT 'private',
    shared_note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_events_user_id (user_id),
    INDEX idx_start_at (start_at),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_events_visibility (visibility),
    INDEX idx_recurrence (recurrence, recurrence_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS friendships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id INT UNSIGNED NOT NULL,
    addressee_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    UNIQUE KEY uniq_friendship_direction (requester_id, addressee_id),
    INDEX idx_friendship_addressee (addressee_id, status),
    INDEX idx_friendship_requester (requester_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_shares (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_event_share (event_id, user_id),
    INDEX idx_event_share_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
