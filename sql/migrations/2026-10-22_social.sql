-- Rede de utilizadores: administrador, treinadores, conexões e visibilidade dos treinos.

ALTER TABLE users
    ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER lang,
    ADD COLUMN is_coach TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin;

-- private = só o atleta (e os seus treinadores); connections = também as conexões; public = todos
ALTER TABLE workouts
    ADD COLUMN visibility ENUM('private','connections','public') NOT NULL DEFAULT 'private' AFTER active;

CREATE TABLE IF NOT EXISTS connections (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id INT UNSIGNED NOT NULL,
    addressee_id INT UNSIGNED NOT NULL,
    status       ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at  DATETIME NULL,
    UNIQUE KEY (requester_id, addressee_id),
    INDEX (addressee_id, status),
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (addressee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Treinador -> atleta. Depois de aceite, o treinador vê todos os treinos do atleta.
CREATE TABLE IF NOT EXISTS coach_athletes (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coach_id     INT UNSIGNED NOT NULL,
    athlete_id   INT UNSIGNED NOT NULL,
    requested_by INT UNSIGNED NOT NULL,
    status       ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at  DATETIME NULL,
    UNIQUE KEY (coach_id, athlete_id),
    INDEX (athlete_id, status),
    FOREIGN KEY (coach_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (athlete_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

UPDATE users SET is_admin = 1 WHERE email = 'antoniovicentetravassos@gmail.com';
