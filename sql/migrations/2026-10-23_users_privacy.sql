-- Quem vê cada campo do perfil (JSON campo => private|connections|public; o que faltar usa o valor por omissão).
ALTER TABLE users ADD COLUMN IF NOT EXISTS privacy TEXT NULL AFTER bio;
