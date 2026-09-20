<?php
/**
 * mailer.php - Envoi d'e-mails avec pièce jointe, sans dépendance externe.
 *
 * Deux modes, selon config `mail` :
 *   · SMTP authentifié (recommandé sur hébergement mutualisé type Octenium /
 *     cPanel) si `mail.smtp.host` est renseigné : connexion SSL/TLS + AUTH LOGIN.
 *   · Repli sur la fonction PHP mail() si SMTP non configuré mais mail.enabled.
 *
 * Construit un message multipart/mixed (corps texte + pièces jointes base64).
 */

/**
 * @param array $attachments Liste de ['name'=>..., 'data'=>..., 'type'=>...].
 * @return array ['ok'=>bool, 'error'=>string]
 */
function mailer_send(string $to, string $subject, string $textBody, array $attachments = []): array
{
    $cfg      = $GLOBALS['config']['mail'] ?? [];
    $fromMail = $cfg['from_email'] ?? ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $fromName = $cfg['from_name'] ?? 'La Bibliothèque Numérique';

    $boundary = 'bnd_' . bin2hex(random_bytes(10));
    $eol = "\r\n";

    // --- Corps MIME (multipart/mixed) ---
    $body  = '--' . $boundary . $eol;
    $body .= 'Content-Type: text/plain; charset=UTF-8' . $eol;
    $body .= 'Content-Transfer-Encoding: 8bit' . $eol . $eol;
    $body .= $textBody . $eol . $eol;
    foreach ($attachments as $att) {
        $body .= '--' . $boundary . $eol;
        $body .= 'Content-Type: ' . ($att['type'] ?? 'application/octet-stream') . '; name="' . $att['name'] . '"' . $eol;
        $body .= 'Content-Transfer-Encoding: base64' . $eol;
        $body .= 'Content-Disposition: attachment; filename="' . $att['name'] . '"' . $eol . $eol;
        $body .= chunk_split(base64_encode($att['data'])) . $eol;
    }
    $body .= '--' . $boundary . '--' . $eol;

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $ctype = 'multipart/mixed; boundary="' . $boundary . '"';

    // --- Envoi ---
    $smtp = $cfg['smtp'] ?? [];
    if (!empty($smtp['host'])) {
        return smtp_deliver($smtp, $fromMail, $fromName, $to, $encodedSubject, $ctype, $body);
    }

    if (empty($cfg['enabled'])) {
        return ['ok' => false, 'error' => 'Aucun serveur de messagerie configuré (mail.enabled = false).'];
    }
    // Repli mail()
    $headers  = 'From: ' . $fromName . ' <' . $fromMail . '>' . $eol;
    $headers .= 'MIME-Version: 1.0' . $eol;
    $headers .= 'Content-Type: ' . $ctype;
    $ok = @mail($to, $encodedSubject, $body, $headers);
    return ['ok' => $ok, 'error' => $ok ? '' : "L'envoi via mail() a échoué."];
}

/**
 * Livraison SMTP (SSL/TLS + AUTH LOGIN). Retourne ['ok'=>bool,'error'=>string].
 */
function smtp_deliver(array $smtp, string $fromMail, string $fromName, string $to, string $subject, string $ctype, string $body): array
{
    $host = $smtp['host'];
    $port = (int) ($smtp['port'] ?? 465);
    $enc  = strtolower((string) ($smtp['encryption'] ?? 'ssl')); // ssl | tls | ''
    $user = $smtp['username'] ?? '';
    $pass = $smtp['password'] ?? '';
    $eol  = "\r\n";

    $remote = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { return ['ok' => false, 'error' => "Connexion SMTP impossible ($host:$port) : $errstr"]; }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') { break; } // dernière ligne (code suivi d'un espace)
        }
        return $data;
    };
    $cmd = function (string $c) use ($fp, $read): string { fwrite($fp, $c . "\r\n"); return $read(); };
    $code = fn(string $r): int => (int) substr(ltrim($r), 0, 3);

    $err = null;
    $expect = function (string $resp, array $ok) use (&$err, $code): bool {
        if (in_array($code($resp), $ok, true)) { return true; }
        $err = trim($resp); return false;
    };

    try {
        if (!$expect($read(), [220])) { throw new RuntimeException($err); }
        $ehloHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (!$expect($cmd('EHLO ' . $ehloHost), [250])) { throw new RuntimeException($err); }

        if ($enc === 'tls') {
            if (!$expect($cmd('STARTTLS'), [220])) { throw new RuntimeException($err); }
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                throw new RuntimeException('Échec de la négociation TLS.');
            }
            if (!$expect($cmd('EHLO ' . $ehloHost), [250])) { throw new RuntimeException($err); }
        }

        if ($user !== '') {
            if (!$expect($cmd('AUTH LOGIN'), [334])) { throw new RuntimeException($err); }
            if (!$expect($cmd(base64_encode($user)), [334])) { throw new RuntimeException($err); }
            if (!$expect($cmd(base64_encode($pass)), [235])) { throw new RuntimeException('Authentification SMTP refusée.'); }
        }

        if (!$expect($cmd('MAIL FROM:<' . $fromMail . '>'), [250])) { throw new RuntimeException($err); }
        if (!$expect($cmd('RCPT TO:<' . $to . '>'), [250, 251])) { throw new RuntimeException($err); }
        if (!$expect($cmd('DATA'), [354])) { throw new RuntimeException($err); }

        $headers  = 'Date: ' . date('r') . $eol;
        $headers .= 'From: ' . mb_encode_mimeheader($fromName) . ' <' . $fromMail . '>' . $eol;
        $headers .= 'To: <' . $to . '>' . $eol;
        $headers .= 'Subject: ' . $subject . $eol;
        $headers .= 'MIME-Version: 1.0' . $eol;
        $headers .= 'Content-Type: ' . $ctype . $eol;

        // Dot-stuffing : les lignes commençant par '.' sont doublées.
        $message = $headers . $eol . $body;
        $message = preg_replace('/^\./m', '..', $message);
        fwrite($fp, $message . $eol . '.' . $eol);
        if (!$expect($read(), [250])) { throw new RuntimeException($err); }

        $cmd('QUIT');
        fclose($fp);
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        @fclose($fp);
        return ['ok' => false, 'error' => 'SMTP : ' . $e->getMessage()];
    }
}
