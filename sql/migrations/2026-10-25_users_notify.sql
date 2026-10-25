-- Notificações por email (1 = enviar): pedidos de conexão/treino e treinos novos dos atletas.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS notify_requests TINYINT(1) NOT NULL DEFAULT 1 AFTER privacy,
    ADD COLUMN IF NOT EXISTS notify_workouts TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_requests;
