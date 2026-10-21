# Séries

Caderno de treino online para atletas de pista: regista séries (ex.: 10 × 500 m com 70 s de recuperação), tempo a tempo, e acompanha recordes, volume e evolução.

PHP 8.1+ sem framework · MySQL/MariaDB · HTML/CSS/JS sem build.

## Estrutura

```
public/          raiz web (só esta pasta deve ser servida)
  assets/        css, js, favicon
  uploads/       avatares (execução de scripts bloqueada)
  *.php          páginas (cada ficheiro = uma rota)
src/             lógica: bootstrap, auth, workouts (dados + estatísticas), i18n
src/lang/        traduções pt.php / en.php
views/           layout, páginas e parciais
config/          config.php (não versionar) e config.sample.php
sql/             schema.sql e seed_demo.php
storage/         ficheiros GPX e logs (fora da raiz web)
```

## Instalação local (XAMPP)

1. `mysql -u root < sql/schema.sql`
2. Copiar `config/config.sample.php` para `config/config.php` e ajustar a base de dados.
3. Em `apache/conf/httpd.conf` incluir `conf/extra/httpd-series.conf` (Alias `/series` → `public/`).
4. Dados de demonstração (opcional): `php sql/seed_demo.php` cria `demo@series.local` / `demo1234`.

Em modo `debug`, os emails (recuperação de password) são escritos em `storage/logs/mail.log`.

## Produção (Linux)

- DocumentRoot a apontar para `public/`; `base_url` em config conforme o caminho (ex.: `''` num domínio próprio).
- Criar um utilizador MySQL só com `SELECT, INSERT, UPDATE, DELETE` na base `series`.
- `debug => false`, HTTPS obrigatório (o cookie de sessão passa a `secure` automaticamente).
- Permissões de escrita para o servidor web em `storage/` e `public/uploads/avatars/`.
- `mail()` configurado no servidor, ou trocar `send_mail()` por SMTP.

## Fase 2 (pendente)

- Login com Google (OAuth): precisa de `client_id`/`client_secret` em config; `public/auth_google.php` é o ponto de entrada.
- Visualização do percurso GPX em mapa e cálculo de parciais a partir do GPX.
- Mais idiomas: adicionar `src/lang/xx.php` e a entrada em `LANGS` (`src/i18n.php`).
