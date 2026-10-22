-- Contas desativadas pelo administrador: não entram e ficam escondidas da rede, mas os dados mantêm-se.
ALTER TABLE users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_coach;
