<?php
/**
 * Diagnóstico do SMTP: com o utilizador e a password de config/config.php experimenta
 * as portas 465 (SSL) e 587 (STARTTLS) e as várias formas de autenticação, e mostra o
 * que o servidor responde. Não mostra o utilizador nem a password.
 *
 *   php tools/smtp_check.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit;
}

$config = require dirname(__DIR__) . '/config/config.php';
$smtp = $config['smtp'] ?? [];
$host = (string)($smtp['host'] ?? '');
$user = (string)($smtp['user'] ?? '');
$pass = (string)($smtp['pass'] ?? '');
if ($host === '' || $user === '') {
    exit("Falta 'smtp' => host/user em config/config.php\n");
}
printf("host=%s user=%s password=%d caracteres\n", $host, $user, strlen($pass));

$plain = base64_encode("\0$user\0$pass");
// cada forma: lista de [comando, mostrar como]
$methods = [
    'PLAIN numa linha'   => [["AUTH PLAIN $plain", 'AUTH PLAIN ****']],
    'PLAIN em 2 passos'  => [['AUTH PLAIN', null], [$plain, '****']],
    'LOGIN'              => [['AUTH LOGIN', null], [base64_encode($user), '**** (utilizador)'], [base64_encode($pass), '**** (password)']],
];

function reply($fp): string
{
    $r = '';
    while (($l = fgets($fp, 515)) !== false) {
        $r .= $l;
        if (($l[3] ?? ' ') === ' ') {
            break;
        }
    }
    return $r;
}

function say($fp, string $line, ?string $shown): string
{
    fwrite($fp, "$line\r\n");
    $r = reply($fp);
    $last = trim((string)strrchr("\n" . trim($r), "\n"));
    printf("   > %-28s < %s\n", $shown ?? $line, $r === '' ? '(ligação fechada)' : $last);
    return $r;
}

foreach ([[465, 'ssl'], [587, 'tls']] as [$port, $secure]) {
    foreach ($methods as $name => $steps) {
        echo "\n== porta $port ($secure), $name\n";
        $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . "$host:$port", $errno, $errstr, 15);
        if (!$fp) {
            echo "   ligação falhou: $errstr\n";
            continue;
        }
        stream_set_timeout($fp, 15);
        echo '   < ', trim(reply($fp)), "\n";
        say($fp, 'EHLO series.corridas.eu', null);
        if ($secure === 'tls') {
            say($fp, 'STARTTLS', null);
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                echo "   STARTTLS falhou\n";
                fclose($fp);
                continue;
            }
            say($fp, 'EHLO series.corridas.eu', null);
        }
        $ok = false;
        foreach ($steps as [$line, $shown]) {
            $r = say($fp, $line, $shown);
            $code = (int)substr($r, 0, 3);
            if ($code === 235) {
                $ok = true;
            }
            if ($code !== 334) {
                break;
            }
        }
        echo $ok ? "   >>> AUTENTICAÇÃO OK\n" : "   >>> falhou\n";
        @fwrite($fp, "QUIT\r\n");
        fclose($fp);
    }
}
