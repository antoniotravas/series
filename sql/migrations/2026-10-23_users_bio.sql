-- Resumo do currículo do treinador, mostrado no perfil.
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER main_event;
