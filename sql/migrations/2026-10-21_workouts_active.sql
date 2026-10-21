-- Treinos inativos: ficam guardados e visíveis na lista, mas não contam nas estatísticas.
ALTER TABLE workouts ADD COLUMN IF NOT EXISTS active TINYINT(1) NOT NULL DEFAULT 1 AFTER gpx_file;
