-- Frequência cardíaca média de cada repetição (preenchida a partir do GPX).
ALTER TABLE workout_reps ADD COLUMN IF NOT EXISTS hr_avg SMALLINT UNSIGNED NULL AFTER time_seconds;
