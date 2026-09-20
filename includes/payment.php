<?php
/**
 * payment.php - Passerelle de paiement BaridiMob.
 *
 * ⚙️ MODE ACTUEL : « manuel ». Le client effectue un virement BaridiMob vers le
 *    RIP de notre équipe, saisit sa référence de transaction, puis notre équipe
 *    valide la commande depuis l'admin (ce qui débloque l'accès au livre).
 *
 * 🔌 QUAND L'API BARIDIMOB SERA DISPONIBLE :
 *    Il suffira d'implémenter les 3 méthodes marquées « TODO API » ci-dessous et
 *    de passer le réglage `baridimob_api_enabled` à 1 (admin › Paramètres).
 *    - initiate()      : créer une intention de paiement + renvoyer l'URL de redirection.
 *    - handleCallback(): traiter le webhook de confirmation (endpoint api/payment-callback.php).
 *    - verify()        : vérifier l'état d'une transaction.
 *    Le reste de l'application (commande, accès livre) reste inchangé.
 */

final class BaridimobGateway
{
    /** L'API automatique est-elle active ? (false tant qu'on n'a pas les accès) */
    public static function apiEnabled(): bool
    {
        return (bool) setting('baridimob_api_enabled', '0') === true
            || setting('baridimob_api_enabled', '0') === '1';
    }

    /** Informations de virement manuel à afficher au client. */
    public static function manualInstructions(): array
    {
        return [
            'rip'  => setting('baridimob_rip', '007 9999 0001 2345 6789'),
            'name' => setting('baridimob_name', 'LA BIBLIOTHEQUE NUMERIQUE'),
            'note' => setting('baridimob_note', 'Après votre virement, indiquez la référence de la transaction. Votre accès sera validé sous 24h.'),
        ];
    }

    /**
     * Démarre le paiement d'une commande.
     * Mode manuel : renvoie ['mode' => 'manual'] (affichage des instructions).
     * Mode API    : TODO API - créer l'intention et renvoyer ['mode'=>'redirect','url'=>...].
     */
    public static function initiate(array $order): array
    {
        if (!self::apiEnabled()) {
            return ['mode' => 'manual', 'order' => $order];
        }
        // TODO API : appel à l'API BaridiMob pour créer un paiement, puis :
        // return ['mode' => 'redirect', 'url' => $paymentUrl];
        return ['mode' => 'manual', 'order' => $order];
    }

    /**
     * Webhook de confirmation (appelé par api/payment-callback.php).
     * TODO API : vérifier la signature, retrouver la commande via la référence,
     * puis appeler mark_order_paid($orderId).
     */
    public static function handleCallback(array $payload): bool
    {
        return false; // inactif en mode manuel
    }

    /** Vérifie l'état d'une transaction. TODO API. */
    public static function verify(string $reference): array
    {
        return ['status' => 'unknown', 'reference' => $reference];
    }
}

/**
 * ---------------------------------------------------------------------------
 *  SatimGateway - Paiement en ligne CIB / Edahabia via SATIM-IPAY.
 * ---------------------------------------------------------------------------
 *  Flux :
 *   1. register()  -> enregistre la commande sur SATIM, renvoie orderId + formUrl.
 *   2. Le site redirige le client vers formUrl (page de paiement SATIM).
 *   3. SATIM redirige vers returnUrl (succès) ou failUrl (échec) avec ?orderId=.
 *   4. confirm()    -> acknowledgeTransaction.do, renvoie l'état réel de la commande.
 *   5. classify()   -> traduit la réponse en accepté / rejeté / erreur.
 *   6. refund()     -> remboursement total ou partiel d'une transaction déposée.
 *
 *  Les montants sont exprimés en centimes (montant DA x 100). Devise 012 (DZD).
 *  Un mode « mock » (config satim.mock) simule SATIM en local pour les tests.
 * ---------------------------------------------------------------------------
 */
final class SatimGateway
{
    /** Configuration SATIM (avec valeurs par défaut). */
    public static function config(): array
    {
        $c = $GLOBALS['config']['satim'] ?? [];
        return $c + [
            'enabled' => false, 'mock' => false,
            'base_url' => 'https://test2.satim.dz/payment/rest/',
            'username' => '', 'password' => '', 'terminal_id' => '',
            'currency' => '012', 'language' => 'FR', 'green_number' => '3020',
            'timeout' => 20, 'recaptcha_site_key' => '', 'recaptcha_secret_key' => '',
        ];
    }

    public static function enabled(): bool
    {
        $c = self::config();
        return !empty($c['enabled']) && (self::isMock() || ($c['username'] !== '' && $c['password'] !== '' && $c['terminal_id'] !== ''));
    }

    public static function isMock(): bool
    {
        return !empty(self::config()['mock']);
    }

    public static function greenNumber(): string
    {
        return (string) self::config()['green_number'];
    }

    /** Montant DA -> centimes attendus par SATIM (x100). */
    public static function amountToMinor($amountDa): int
    {
        return (int) round(((float) $amountDa) * 100);
    }

    /** Numéro de commande marchand : alphanumérique unique, <= 10 caractères. */
    public static function newOrderNumber(): string
    {
        return strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
    }

    /**
     * Enregistre une commande sur SATIM.
     * @return array ['ok'=>bool,'orderId'=>?string,'formUrl'=>?string,'error'=>string,'raw'=>array]
     */
    public static function register(string $orderNumber, int $amountMinor, string $returnUrl, string $failUrl, string $description = ''): array
    {
        $c = self::config();
        $params = [
            'userName'    => $c['username'],
            'password'    => $c['password'],
            'orderNumber' => $orderNumber,
            'amount'      => $amountMinor,
            'currency'    => $c['currency'],
            'returnUrl'   => $returnUrl,
            'failUrl'     => $failUrl,
            'language'    => $c['language'],
            'jsonParams'  => json_encode([
                'force_terminal_id' => $c['terminal_id'],
                'udf1'              => $orderNumber,
            ], JSON_UNESCAPED_SLASHES),
        ];
        if ($description !== '') { $params['description'] = mb_substr($description, 0, 512); }

        $res = self::httpCall('register.do', $params);
        if (!$res['ok']) {
            return ['ok' => false, 'orderId' => null, 'formUrl' => null, 'error' => $res['error'], 'raw' => $res['data']];
        }
        $d = $res['data'];
        $errorCode = (string) ($d['errorCode'] ?? '1');
        if ($errorCode !== '0' || empty($d['formUrl'])) {
            return ['ok' => false, 'orderId' => $d['orderId'] ?? null, 'formUrl' => null,
                    'error' => self::registerErrorMessage($errorCode, $d), 'raw' => $d];
        }
        return ['ok' => true, 'orderId' => $d['orderId'], 'formUrl' => $d['formUrl'], 'error' => '', 'raw' => $d];
    }

    /** Confirme/interroge l'état d'une commande. Renvoie le tableau décodé. */
    public static function confirm(string $mdOrder): array
    {
        $c = self::config();
        $res = self::httpCall('public/acknowledgeTransaction.do', [
            'userName' => $c['username'],
            'password' => $c['password'],
            'mdOrder'  => $mdOrder,
            'language' => $c['language'],
        ]);
        return $res['ok'] ? $res['data'] : ['_error' => $res['error']];
    }

    /** Remboursement (montant en DA). */
    public static function refund(string $orderId, $amountDa): array
    {
        $c = self::config();
        $res = self::httpCall('refund.do', [
            'userName' => $c['username'],
            'password' => $c['password'],
            'orderId'  => $orderId,
            'amount'   => self::amountToMinor($amountDa),
            'currency' => $c['currency'],
        ]);
        if (!$res['ok']) { return ['ok' => false, 'error' => $res['error']]; }
        $code = (string) ($res['data']['errorCode'] ?? '1');
        return ['ok' => $code === '0', 'error' => $code === '0' ? '' : ($res['data']['errorMessage'] ?? 'Erreur de remboursement (' . $code . ').'), 'raw' => $res['data']];
    }

    /**
     * Traduit une réponse confirm() selon la checklist SATIM.
     * @return array ['result'=>'accepted'|'rejected'|'declined'|'error','message'=>string,'data'=>array]
     */
    public static function classify(array $c): array
    {
        if (isset($c['_error'])) {
            return ['result' => 'error', 'message' => 'La connexion au serveur de paiement a échoué. ' . $c['_error'], 'data' => $c];
        }
        $params      = $c['params'] ?? [];
        $respCode    = (string) ($params['respCode'] ?? '');
        $respDesc    = trim((string) ($params['respCode_desc'] ?? ''));
        $errorCode   = (string) ($c['ErrorCode'] ?? $c['errorCode'] ?? '');
        $orderStatus = (string) ($c['OrderStatus'] ?? '');
        $actionDesc  = trim((string) ($c['actionCodeDescription'] ?? ''));

        // Paiement accepté : respCode 00, ErrorCode 0 et OrderStatus 2.
        if ($respCode === '00' && $errorCode === '0' && $orderStatus === '2') {
            return ['result' => 'accepted', 'message' => $respDesc ?: 'Votre paiement a été accepté', 'data' => $c];
        }
        // Paiement rejeté explicite : respCode 00, ErrorCode 0 et OrderStatus 3.
        if ($respCode === '00' && $errorCode === '0' && $orderStatus === '3') {
            return ['result' => 'rejected', 'message' => 'Votre transaction a été rejetée / Your transaction was rejected / تم رفض معاملتك', 'data' => $c];
        }
        // Sinon : afficher respCode_desc, sinon actionCodeDescription.
        return ['result' => 'declined', 'message' => $respDesc ?: ($actionDesc ?: 'Votre transaction a été rejetée / Your transaction was rejected / تم رفض معاملتك'), 'data' => $c];
    }

    /** Marque de carte déduite du PAN (préfixe). */
    public static function cardBrand(?string $pan): string
    {
        $pan = preg_replace('/\D/', '', (string) $pan);
        if ($pan === '') { return 'CIB / Edahabia'; }
        // Edahabia (Algérie Poste) : préfixe 6280 62 ; CIB : 6280 58 / 6280 61 ...
        if (str_starts_with($pan, '628006') || str_starts_with($pan, '628062')) { return 'Edahabia'; }
        return 'CIB';
    }

    /* --------------------------------------------------------------------- */

    /** Appel HTTP POST (recommandé par SATIM) ou simulateur local. */
    private static function httpCall(string $action, array $params): array
    {
        if (self::isMock()) {
            require_once __DIR__ . '/satim_mock.php';
            return satim_mock_call($action, $params);
        }
        $c   = self::config();
        $url = rtrim($c['base_url'], '/') . '/' . ltrim($action, '/');

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'data' => [], 'raw' => '', 'error' => 'cURL indisponible sur le serveur.'];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int) $c['timeout'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '') {
            return ['ok' => false, 'data' => [], 'raw' => '', 'error' => $err ?: ('Réponse vide (HTTP ' . $code . ').')];
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            return ['ok' => false, 'data' => [], 'raw' => (string) $body, 'error' => 'Réponse SATIM illisible (HTTP ' . $code . ').'];
        }
        return ['ok' => true, 'data' => $data, 'raw' => (string) $body, 'error' => ''];
    }

    private static function registerErrorMessage(string $code, array $d): string
    {
        $map = [
            '1' => 'Numéro de commande déjà utilisé ou commande déjà traitée.',
            '3' => 'Devise inconnue.',
            '4' => 'Un paramètre obligatoire est manquant.',
            '5' => 'Valeur de paramètre incorrecte ou accès refusé.',
            '7' => 'Erreur système SATIM.',
            '14'=> 'Moyen de paiement invalide.',
        ];
        return ($d['errorMessage'] ?? $map[$code] ?? 'Erreur SATIM') . ' (code ' . $code . ').';
    }
}

/**
 * Migration légère : ajoute les colonnes SATIM à la table orders si absentes.
 * Idempotent - sûr à appeler à chaque requête (coût négligeable, mis en cache).
 */
function satim_ensure_schema(): void
{
    static $done = false;
    if ($done) { return; }
    $done = true;
    try {
        $driver = Database::driver();
        $cols = ['order_number','satim_order_id','approval_code','resp_code','pan','card_brand','paid_at'];
        if ($driver === 'sqlite') {
            $existing = array_column(Database::all('PRAGMA table_info(orders)'), 'name');
        } else {
            $existing = array_column(Database::all('SHOW COLUMNS FROM orders'), 'Field');
        }
        $types = [
            'order_number'   => 'VARCHAR(20)',  'satim_order_id' => 'VARCHAR(40)',
            'approval_code'  => 'VARCHAR(12)',  'resp_code'      => 'VARCHAR(8)',
            'pan'            => 'VARCHAR(24)',   'card_brand'     => 'VARCHAR(20)',
            'paid_at'        => ($driver === 'sqlite' ? 'TEXT' : 'DATETIME'),
        ];
        foreach ($cols as $col) {
            if (!in_array($col, $existing, true)) {
                $t = $driver === 'sqlite' ? 'TEXT' : $types[$col];
                Database::run("ALTER TABLE orders ADD COLUMN $col $t NULL");
            }
        }
    } catch (Throwable $e) { /* non bloquant */ }
}

/**
 * Marque une commande comme payée et débloque l'accès aux livres achetés.
 * Utilisé par la validation admin (et, plus tard, par le webhook API).
 */
function mark_order_paid(int $orderId): void
{
    $order = Database::first('SELECT * FROM orders WHERE id = ?', [$orderId]);
    if (!$order || $order['status'] === 'paid') { return; }

    Database::run('UPDATE orders SET status = ? WHERE id = ?', ['paid', $orderId]);

    if ($order['customer_id']) {
        $items = Database::all('SELECT book_id FROM order_items WHERE order_id = ?', [$orderId]);
        foreach ($items as $it) {
            if (!$it['book_id']) { continue; }
            try {
                Database::run(
                    'INSERT INTO book_access (customer_id, book_id, order_id) VALUES (?,?,?)',
                    [$order['customer_id'], $it['book_id'], $orderId]
                );
            } catch (Throwable $e) { /* accès déjà accordé */ }
        }
    }
}
