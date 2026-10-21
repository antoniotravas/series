<?php
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$errors = [];
$section = null;

const AVATAR_MAX_BYTES = 2 * 1024 * 1024;
const AVATAR_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

function remove_avatar_file(?string $name): void
{
    if ($name) {
        @unlink(ROOT . '/public/uploads/avatars/' . basename($name));
    }
}

if (is_post()) {
    csrf_check();
    $section = (string)($_POST['action'] ?? '');

    if ($section === 'profile') {
        $name = trim((string)($_POST['display_name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 80) {
            $errors['display_name'] = t('error.name_required');
        }
        $num = function (string $key, float $min, float $max) use (&$errors): ?float {
            $v = trim(str_replace(',', '.', (string)($_POST[$key] ?? '')));
            if ($v === '') {
                return null;
            }
            if (!is_numeric($v) || $v < $min || $v > $max) {
                $errors[$key] = t('error.number');
                return null;
            }
            return (float)$v;
        };
        $weight = $num('weight_kg', 20, 250);
        $height = $num('height_cm', 100, 250);
        $birth = (string)($_POST['birth_date'] ?? '');
        if ($birth !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth) || $birth > date('Y-m-d'))) {
            $errors['birth_date'] = t('error.date_invalid');
        }
        $sex = in_array($_POST['sex'] ?? '', ['M', 'F', 'X'], true) ? $_POST['sex'] : null;
        $country = strtoupper(substr(preg_replace('/[^a-z]/i', '', (string)($_POST['country'] ?? '')), 0, 2)) ?: null;
        $lang = isset(LANGS[$_POST['lang'] ?? '']) ? $_POST['lang'] : $user['lang'];

        // avatar
        $avatar = $user['avatar'];
        if (!empty($_POST['remove_avatar'])) {
            remove_avatar_file($avatar);
            $avatar = null;
        }
        $f = $_FILES['avatar'] ?? null;
        if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
            $mime = $f['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';
            if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > AVATAR_MAX_BYTES || !isset(AVATAR_TYPES[$mime]) || !getimagesize($f['tmp_name'])) {
                $errors['avatar'] = t('error.avatar');
            } else {
                $newName = $uid . '_' . bin2hex(random_bytes(6)) . '.' . AVATAR_TYPES[$mime];
                if (move_uploaded_file($f['tmp_name'], ROOT . '/public/uploads/avatars/' . $newName)) {
                    remove_avatar_file($avatar);
                    $avatar = $newName;
                }
            }
        }

        if (!$errors) {
            q(
                'UPDATE users SET display_name = ?, birth_date = ?, sex = ?, weight_kg = ?, height_cm = ?,
                        club = ?, country = ?, main_event = ?, lang = ?, avatar = ? WHERE id = ?',
                [
                    $name, $birth ?: null, $sex, $weight, $height === null ? null : (int)$height,
                    mb_substr(trim((string)($_POST['club'] ?? '')), 0, 120) ?: null,
                    $country,
                    mb_substr(trim((string)($_POST['main_event'] ?? '')), 0, 40) ?: null,
                    $lang, $avatar, $uid,
                ]
            );
            set_lang($lang);
            flash('ok', t('p.saved'));
            redirect('profile.php');
        }
    } elseif ($section === 'email') {
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = t('error.email_invalid');
        } elseif ($email !== $user['email'] && q('SELECT 1 FROM users WHERE email = ?', [$email])->fetchColumn()) {
            $errors['email'] = t('error.email_taken');
        } elseif ($user['password_hash'] && !password_verify((string)($_POST['current_password'] ?? ''), $user['password_hash'])) {
            $errors['email_current_password'] = t('p.pw_wrong');
        } else {
            q('UPDATE users SET email = ? WHERE id = ?', [$email, $uid]);
            flash('ok', t('p.email_saved'));
            redirect('profile.php');
        }
    } elseif ($section === 'password') {
        if ($user['password_hash'] && !password_verify((string)($_POST['current_password'] ?? ''), $user['password_hash'])) {
            $errors['current_password'] = t('p.pw_wrong');
        } else {
            $errors = validate_password((string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''));
        }
        if (!$errors) {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            flash('ok', t('p.pw_saved'));
            redirect('profile.php');
        }
    } elseif ($section === 'delete') {
        if ($user['password_hash'] && !password_verify((string)($_POST['current_password'] ?? ''), $user['password_hash'])) {
            $errors['delete_password'] = t('p.pw_wrong');
        } else {
            remove_avatar_file($user['avatar']);
            q('DELETE FROM users WHERE id = ?', [$uid]);
            logout_user();
            flash('ok', t('p.deleted'));
            redirect('');
        }
    }
    if ($errors) {
        keep_old($_POST);
    }
}

render('profile', [
    'title'   => t('p.title'),
    'active'  => 'profile',
    'u'       => $user,
    'errors'  => $errors,
    'section' => $section,
]);
clear_old();
