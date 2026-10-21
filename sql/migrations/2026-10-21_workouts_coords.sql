-- Coordenadas do local do treino (centro das repetições do GPX): permitem reconhecer
-- o mesmo local em treinos seguintes e usar o nome que o atleta lhe deu.
ALTER TABLE workouts
    ADD COLUMN IF NOT EXISTS lat DECIMAL(9,6) NULL AFTER location,
    ADD COLUMN IF NOT EXISTS lon DECIMAL(9,6) NULL AFTER lat;
