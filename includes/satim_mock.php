<?php
/**
 * satim_mock.php - Simulateur SATIM local (config satim.mock = true).
 *
 * Reproduit register.do / acknowledgeTransaction.do / refund.do et le jeu des
 * 15 cartes de test de la certification, SANS aucun accès réseau. Permet de
 * tester tout le parcours de paiement hors-ligne. Inerte en production
 * (n'est chargé que lorsque satim.mock vaut true).
 */

/** Jeu de cartes de test SATIM -> résultat simulé. */
function satim_mock_cards(): array
{
    return [
        '6280581110007215' => ['result' => 'accepted', 'desc' => 'Votre paiement a été accepté'],
        '6280581110006712' => ['result' => 'declined', 'desc' => 'Carte temporairement bloquée', 'resp' => '116'],
        '6280581110006316' => ['result' => 'declined', 'desc' => 'Carte déclarée perdue', 'resp' => '041'],
        '6280581110006415' => ['result' => 'declined', 'desc' => 'Carte déclarée volée', 'resp' => '043'],
        '6280581110006613' => ['result' => 'declined', 'desc' => "Date d'expiration incorrecte", 'resp' => '054'],
        '6280581110003927' => ['result' => 'declined', 'desc' => "Carte inexistante sur le serveur de l'émetteur", 'resp' => '056'],
        '6280580610061219' => ['result' => 'declined', 'desc' => 'Dépassement du plafond de la carte', 'resp' => '061'],
        '6280580610061110' => ['result' => 'declined', 'desc' => 'Solde insuffisant', 'resp' => '051'],
        '6280581110006514' => ['result' => 'declined', 'desc' => 'CVV2 erroné', 'resp' => '082', 'blank_desc' => true],
        '6280580610061318' => ['result' => 'declined', 'desc' => 'Nombre de mots de passe erronés dépassé (3 essais)', 'resp' => '075'],
        '6280581110007017' => ['result' => 'declined', 'desc' => 'Carte non autorisée pour le paiement en ligne', 'resp' => '062'],
        '6280581110007116' => ['result' => 'declined', 'desc' => 'Carte inactive pour le paiement en ligne', 'resp' => '078'],
        '6280581110007314' => ['result' => 'declined', 'desc' => 'Dépassement du plafond du terminal', 'resp' => '065', 'blank_desc' => true],
        '6280580610056615' => ['result' => 'declined', 'desc' => 'Carte expirée', 'resp' => '033'],
        '6280580610061011' => ['result' => 'accepted', 'desc' => 'Votre paiement a été accepté'],
    ];
}

function satim_mock_store_path(): string
{
    return ROOT_PATH . '/database/_satim_mock.json';
}

function satim_mock_load(): array
{
    $p = satim_mock_store_path();
    if (!is_file($p)) { return []; }
    $d = json_decode((string) @file_get_contents($p), true);
    return is_array($d) ? $d : [];
}

function satim_mock_save(array $data): void
{
    @file_put_contents(satim_mock_store_path(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/** Point d'entrée appelé par SatimGateway::httpCall() en mode mock. */
function satim_mock_call(string $action, array $params): array
{
    $store = satim_mock_load();

    if ($action === 'register.do') {
        $mdOrder = 'MOCK' . strtoupper(bin2hex(random_bytes(8)));
        $store[$mdOrder] = [
            'orderNumber' => $params['orderNumber'] ?? '',
            'amount'      => (int) ($params['amount'] ?? 0),
            'currency'    => $params['currency'] ?? '012',
            'returnUrl'   => $params['returnUrl'] ?? '',
            'failUrl'     => $params['failUrl'] ?? '',
            'status'      => 'registered',
            'created_at'  => date('c'),
        ];
        satim_mock_save($store);
        return ['ok' => true, 'raw' => '', 'error' => '', 'data' => [
            'errorCode' => 0,
            'orderId'   => $mdOrder,
            'formUrl'   => url('_devsatim-form.php?mdOrder=' . urlencode($mdOrder)),
        ]];
    }

    if ($action === 'public/acknowledgeTransaction.do') {
        $md = $params['mdOrder'] ?? '';
        $rec = $store[$md] ?? null;
        if (!$rec) {
            return ['ok' => true, 'raw' => '', 'error' => '', 'data' => ['ErrorCode' => '6', 'ErrorMessage' => 'Unregistered order Id']];
        }
        $amount = (int) $rec['amount'];
        $orderNumber = $rec['orderNumber'];
        $pan = $rec['pan'] ?? '6280581110007215';
        $masked = substr($pan, 0, 6) . '****' . substr($pan, -4);

        if (($rec['status'] ?? '') === 'accepted') {
            return ['ok' => true, 'raw' => '', 'error' => '', 'data' => [
                'expiration' => '202701', 'cardholderName' => 'TEST CARDHOLDER',
                'depositAmount' => $amount, 'currency' => $rec['currency'],
                'authorizationResponseId' => $rec['approvalCode'], 'approvalCode' => $rec['approvalCode'],
                'actionCode' => 0, 'actionCodeDescription' => 'Votre paiement a été accepté',
                'ErrorCode' => '0', 'ErrorMessage' => 'Success', 'OrderStatus' => 2,
                'OrderNumber' => $orderNumber, 'Pan' => $masked, 'Amount' => $amount,
                'Ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'params' => ['respCode_desc' => 'Votre paiement a été accepté', 'udf1' => $orderNumber, 'respCode' => '00'],
                'SvfeResponse' => '00',
            ]];
        }
        if (($rec['status'] ?? '') === 'refunded') {
            return ['ok' => true, 'raw' => '', 'error' => '', 'data' => [
                'ErrorCode' => '0', 'OrderStatus' => 4, 'OrderNumber' => $orderNumber, 'Amount' => $amount,
                'actionCodeDescription' => 'Transaction remboursée',
                'params' => ['respCode_desc' => 'Votre paiement a été accepté', 'respCode' => '00'],
            ]];
        }
        if (($rec['status'] ?? '') === 'declined') {
            $desc = $rec['desc'] ?? 'Votre transaction a été rejetée';
            return ['ok' => true, 'raw' => '', 'error' => '', 'data' => [
                'actionCode' => (int) ($rec['resp'] ?? 0), 'actionCodeDescription' => $desc,
                'ErrorCode' => '0', 'ErrorMessage' => 'Declined', 'OrderStatus' => 6,
                'OrderNumber' => $orderNumber, 'Amount' => $amount,
                // respCode_desc volontairement vide pour certaines cartes -> teste le repli sur actionCodeDescription
                'params' => ['respCode_desc' => empty($rec['blank_desc']) ? $desc : '', 'udf1' => $orderNumber, 'respCode' => (string) ($rec['resp'] ?? '05')],
            ]];
        }
        // Enregistrée mais non payée
        return ['ok' => true, 'raw' => '', 'error' => '', 'data' => [
            'ErrorCode' => '0', 'OrderStatus' => 0, 'OrderNumber' => $orderNumber, 'Amount' => $amount,
            'actionCodeDescription' => 'Commande enregistrée, non payée',
            'params' => ['respCode' => '00', 'respCode_desc' => ''],
        ]];
    }

    if ($action === 'refund.do') {
        $oid = $params['orderId'] ?? '';
        if (isset($store[$oid]) && ($store[$oid]['status'] ?? '') === 'accepted') {
            $store[$oid]['status'] = 'refunded';
            satim_mock_save($store);
            return ['ok' => true, 'raw' => '', 'error' => '', 'data' => ['errorCode' => 0]];
        }
        return ['ok' => true, 'raw' => '', 'error' => '', 'data' => ['errorCode' => 6, 'errorMessage' => 'Unregistered OrderId or not captured']];
    }

    return ['ok' => false, 'data' => [], 'raw' => '', 'error' => 'Action mock inconnue : ' . $action];
}

/** Applique le résultat d'une carte de test (appelé par _devsatim-form.php). */
function satim_mock_apply_card(string $mdOrder, string $pan): ?array
{
    $store = satim_mock_load();
    if (!isset($store[$mdOrder])) { return null; }
    $pan = preg_replace('/\D/', '', $pan);
    $cards = satim_mock_cards();
    $card = $cards[$pan] ?? ['result' => 'declined', 'desc' => 'Carte de test inconnue', 'resp' => '05'];

    $store[$mdOrder]['pan'] = $pan;
    if ($card['result'] === 'accepted') {
        $store[$mdOrder]['status'] = 'accepted';
        $store[$mdOrder]['approvalCode'] = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    } else {
        $store[$mdOrder]['status'] = 'declined';
        $store[$mdOrder]['desc'] = $card['desc'];
        $store[$mdOrder]['resp'] = $card['resp'] ?? '05';
        $store[$mdOrder]['blank_desc'] = !empty($card['blank_desc']);
    }
    satim_mock_save($store);
    return $store[$mdOrder];
}
