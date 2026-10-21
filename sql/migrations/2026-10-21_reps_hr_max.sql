-- Frequência cardíaca máxima de cada repetição (preenchida a partir do GPX).
ALTER TABLE workout_reps ADD COLUMN IF NOT EXISTS hr_max SMALLINT UNSIGNED NULL AFTER hr_avg;
