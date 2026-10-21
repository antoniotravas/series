-- Esquema da base de dados "series"
-- Compatível com MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS series CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE series;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    google_id     VARCHAR(64)  NULL UNIQUE,
    display_name  VARCHAR(80)  NOT NULL,
    avatar        VARCHAR(120) NULL,
    birth_date    DATE NULL,
    sex           ENUM('M','F','X') NULL,
    weight_kg     DECIMAL(5,1) NULL,
    height_cm     SMALLINT UNSIGNED NULL,
    club          VARCHAR(120) NULL,
    country       CHAR(2) NULL,
    main_event    VARCHAR(40) NULL,
    lang          CHAR(2) NOT NULL DEFAULT 'pt',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at    DATETIME NULL,
    INDEX (token_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip           VARCHAR(45) NOT NULL,
    email        VARCHAR(190) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (ip, attempted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS workouts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(120) NULL,
    workout_date DATE NOT NULL,
    start_time  TIME NULL,
    location    VARCHAR(120) NULL,
    venue_type  ENUM('outdoor','indoor','road','trail','other') NOT NULL DEFAULT 'outdoor',
    weather     VARCHAR(60) NULL,
    temperature_c TINYINT NULL,
    rpe         TINYINT UNSIGNED NULL,
    notes       TEXT NULL,
    gpx_file    VARCHAR(120) NULL,
    active      TINYINT(1) NOT NULL DEFAULT 1,   -- 0 = inativo: fica na lista, não conta nas estatísticas
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (user_id, workout_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Um bloco de repetições: ex. 10 x 500 m com 70 s de recuperação
CREATE TABLE IF NOT EXISTS workout_sets (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workout_id   INT UNSIGNED NOT NULL,
    position     TINYINT UNSIGNED NOT NULL,
    distance_m   SMALLINT UNSIGNED NOT NULL,
    rest_seconds SMALLINT UNSIGNED NULL,
    rest_type    ENUM('stand','walk','jog') NOT NULL DEFAULT 'stand',
    target_seconds DECIMAL(7,2) NULL,
    INDEX (workout_id),
    FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS workout_reps (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    set_id       INT UNSIGNED NOT NULL,
    rep_number   TINYINT UNSIGNED NOT NULL,
    time_seconds DECIMAL(7,2) NOT NULL,
    hr_avg       SMALLINT UNSIGNED NULL,     -- frequência cardíaca média (bpm)
    INDEX (set_id),
    FOREIGN KEY (set_id) REFERENCES workout_sets(id) ON DELETE CASCADE
) ENGINE=InnoDB;
