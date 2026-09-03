<?php
/**
 * payment.php — Passerelle de paiement BaridiMob.
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
     * Mode API    : TODO API — créer l'intention et renvoyer ['mode'=>'redirect','url'=>...].
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
