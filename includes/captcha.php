<?php
/**
 * captcha.php - Protection anti-robot de la page de paiement.
 *
 * Deux modes :
 *   · reCAPTCHA v2 (« I'm not a robot ») si les clés sont renseignées dans la
 *     config SATIM (recaptcha_site_key / recaptcha_secret_key).
 *   · Sinon, un captcha intégré (image SVG générée localement) : aucune
 *     dépendance externe, fonctionne hors-ligne et sur hébergement mutualisé.
 *
 * Le captcha est requis par la certification SATIM sur la page contenant le
 * bouton de paiement.
 */

function captcha_keys(): array
{
    $c = $GLOBALS['config']['satim'] ?? [];
    return [trim((string) ($c['recaptcha_site_key'] ?? '')), trim((string) ($c['recaptcha_secret_key'] ?? ''))];
}

function captcha_uses_recaptcha(): bool
{
    [$site, $secret] = captcha_keys();
    return $site !== '' && $secret !== '';
}

/** Génère et mémorise un nouveau code (captcha intégré). Retourne le code. */
function captcha_new_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sans O/0, I/1 ambigus
    $code = '';
    for ($i = 0; $i < 5; $i++) { $code .= $alphabet[random_int(0, strlen($alphabet) - 1)]; }
    $_SESSION['captcha_code'] = $code;
    $_SESSION['captcha_time'] = time();
    return $code;
}

/** Rendu du champ captcha (à insérer dans le formulaire de paiement). */
function captcha_field(): string
{
    if (captcha_uses_recaptcha()) {
        [$site] = captcha_keys();
        return '<div class="captcha-box"><div class="g-recaptcha" data-sitekey="' . e($site) . '"></div>'
             . '<script src="https://www.google.com/recaptcha/api.js" async defer></script></div>';
    }
    captcha_new_code();
    $src = url('captcha.php');
    ob_start(); ?>
    <div class="captcha-box">
      <label class="captcha-label">Recopiez le code ci-dessous</label>
      <div class="captcha-row">
        <img src="<?php h($src); ?>?t=<?= time() ?>" alt="Code de sécurité" id="captchaImg" class="captcha-img" width="150" height="52">
        <button type="button" class="btn btn-sm btn-ghost" id="captchaReload" title="Nouveau code"><?= icon('refresh') ?></button>
        <input type="text" name="captcha" id="captchaInput" class="captcha-input" autocomplete="off"
               inputmode="text" maxlength="5" placeholder="Code" required aria-label="Code de sécurité">
      </div>
    </div>
    <script>
    (function () {
      var b = document.getElementById('captchaReload'), img = document.getElementById('captchaImg');
      if (b && img) { b.addEventListener('click', function () { img.src = '<?php h($src); ?>?t=' + Date.now(); }); }
    })();
    </script>
    <?php
    return ob_get_clean();
}

/** Vérifie la réponse captcha. Retourne true si valide. */
function captcha_verify(): bool
{
    if (captcha_uses_recaptcha()) {
        [, $secret] = captcha_keys();
        $token = $_POST['g-recaptcha-response'] ?? '';
        if ($token === '') { return false; }
        if (!function_exists('curl_init')) { return false; }
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '']),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string) $body, true);
        return is_array($data) && !empty($data['success']);
    }

    $code  = $_SESSION['captcha_code'] ?? '';
    $when  = (int) ($_SESSION['captcha_time'] ?? 0);
    $given = strtoupper(trim((string) ($_POST['captcha'] ?? '')));
    unset($_SESSION['captcha_code']); // usage unique
    if ($code === '' || $given === '') { return false; }
    if (time() - $when > 600) { return false; }      // expire après 10 min
    return hash_equals($code, $given);
}
