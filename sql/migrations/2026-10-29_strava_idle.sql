-- Ligação ao Strava: desfaz-se após 10 minutos sem uso, exceto para atletas com ligação fixa (escolhidos pelo administrador).
ALTER TABLE users ADD COLUMN IF NOT EXISTS strava_keep TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE strava_accounts ADD COLUMN IF NOT EXISTS last_used INT UNSIGNED NOT NULL DEFAULT 0;
