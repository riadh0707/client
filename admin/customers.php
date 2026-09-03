<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();
$view = (int) input('view');
$detail = $view ? Database::first('SELECT * FROM customers WHERE id = ?', [$view]) : null;
$custOrders = $detail ? Database::all('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC', [$detail['id']]) : [];
$ownedBooks = $detail ? Database::all('SELECT b.title FROM book_access a JOIN books b ON b.id=a.book_id WHERE a.customer_id=?', [$detail['id']]) : [];
$q = input('q');
$where = 'deleted_at IS NULL'; $params = [];
if ($q) { $where .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
$perPage = 15;
$total = (int) Database::scalar("SELECT COUNT(*) FROM customers WHERE $where", $params);
$pg = paginate($total, $perPage, (int) input('page', '1'));
$customers = Database::all("SELECT c.*, (SELECT COUNT(*) FROM orders o WHERE o.customer_id=c.id) ocount, (SELECT COUNT(*) FROM book_access a WHERE a.customer_id=c.id) bcount, (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.customer_id=c.id AND o.status='paid') spent FROM customers c WHERE $where ORDER BY c.id DESC LIMIT $perPage OFFSET {$pg['offset']}", $params);
$statuses = ['pending' => 'À payer', 'awaiting' => 'En validation', 'paid' => 'Payée', 'cancelled' => 'Annulée'];
$adminActive = 'customers'; $adminTitle = 'Clients';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<?php if ($detail): ?>
  <a href="<?php h(url('admin/customers.php')); ?>" class="a-btn ghost sm" style="margin-bottom:16px"><?= icon('arrow','flip') ?> Retour</a>
  <div class="a-grid-2">
    <div class="a-panel">
      <div style="display:flex;gap:14px;align-items:center;margin-bottom:16px"><span class="a-avatar" style="width:60px;height:60px;font-size:1.2rem"><?php h(initials($detail['first_name'].' '.$detail['last_name'])); ?></span><div><h2><?php h($detail['first_name'].' '.$detail['last_name']); ?></h2><span style="color:var(--a-muted)"><?php h($detail['email']); ?></span></div></div>
      <p style="line-height:1.9"><?= icon('phone') ?> <?php h($detail['phone'] ?: 'Non renseigné'); ?><br><?= icon('clock') ?> Inscrite le <?php h(fdate($detail['created_at'])); ?></p>
      <h3 style="margin:16px 0 8px">Bibliothèque (<?= count($ownedBooks) ?>)</h3>
      <?php foreach ($ownedBooks as $ob): ?><span class="a-tag" style="margin:2px;display:inline-block"><?= icon('book') ?> <?php h(excerpt($ob['title'],30)); ?></span><?php endforeach; ?>
    </div>
    <div class="a-panel"><h3 style="margin-bottom:14px">Commandes</h3>
      <?php if (empty($custOrders)): ?><p class="a-empty">Aucune commande.</p><?php else: ?>
      <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Réf.</th><th>Total</th><th>Statut</th><th></th></tr></thead><tbody>
      <?php foreach ($custOrders as $o): ?><tr><td><strong><?php h($o['reference']); ?></strong></td><td><?= money($o['total']) ?></td><td><span class="a-pill st-<?php h($o['status']); ?>"><?php h($statuses[$o['status']] ?? $o['status']); ?></span></td><td><a class="a-iconlink" href="<?php h(url('admin/orders.php?view=' . $o['id'])); ?>"><?= icon('eye') ?></a></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <div class="a-panel">
    <div class="a-panel-head"><form method="get" class="a-search" style="background:var(--a-bg)"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="Rechercher un client…"></form><span class="a-tag"><?= $total ?> clients</span></div>
    <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Client</th><th>E-mail</th><th>Commandes</th><th>Titres</th><th>Dépensé</th><th></th></tr></thead><tbody>
    <?php foreach ($customers as $c): ?><tr><td><div class="a-prodcell"><span class="a-avatar"><?php h(initials($c['first_name'].' '.$c['last_name'])); ?></span><strong><?php h($c['first_name'].' '.$c['last_name']); ?></strong></div></td><td><?php h($c['email']); ?></td><td><span class="a-tag"><?= (int) $c['ocount'] ?></span></td><td><span class="a-tag"><?= (int) $c['bcount'] ?></span></td><td><strong><?= money($c['spent']) ?></strong></td><td><a class="a-iconlink" href="?view=<?= $c['id'] ?>"><?= icon('eye') ?></a></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php require INCLUDES_PATH . '/admin_pagination.php'; ?>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
