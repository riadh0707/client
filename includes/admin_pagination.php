<?php
/** admin_pagination.php — pagination réutilisable admin. Attend $pg. */
if (($pg['pages'] ?? 1) > 1): $qs = $_GET; ?>
<div style="display:flex;gap:6px;justify-content:center;margin-top:20px;flex-wrap:wrap">
  <?php for ($i = 1; $i <= $pg['pages']; $i++): $qs['page'] = $i; ?>
    <a href="?<?= e(http_build_query($qs)) ?>" class="a-btn <?= $i == $pg['current'] ? '' : 'ghost' ?> sm"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
