-- Ligação ao Strava: uma linha por atleta ligado (tokens OAuth).
CREATE TABLE IF NOT EXISTS strava_accounts (
    user_id       INT UNSIGNED NOT NULL PRIMARY KEY,
    athlete_id    BIGINT UNSIGNED NULL,
    access_token  VARCHAR(255) NOT NULL,
    refresh_token VARCHAR(255) NOT NULL,
    expires_at    INT UNSIGNED NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_strava_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
