-- Treinos registados pelo treinador na conta do atleta: guarda quem os criou.
ALTER TABLE workouts ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL AFTER user_id;
ALTER TABLE workouts ADD FOREIGN KEY IF NOT EXISTS fk_workouts_created_by (created_by) REFERENCES users(id) ON DELETE SET NULL;
