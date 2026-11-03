<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();

$id = (int) input('id');
$p = $id ? Database::first('SELECT * FROM products WHERE id = ?', [$id]) : null;
$isNew = !$p;

/**
 * Enregistre un fichier image uploadé dans /uploads/products et renvoie le nom
 * de fichier sûr, ou null si invalide.
 */
function store_product_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { return null; }
    if ($file['size'] > 5 * 1024 * 1024) { return null; }                 // 5 Mo max
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) { return null; }                                  // pas une vraie image
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$info['mime']])) { return null; }
    $name = 'p-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$info['mime']];
    if (!@move_uploaded_file($file['tmp_name'], UPLOADS_PATH . '/products/' . $name)) { return null; }
    return $name;
}

/** Ajoute les images uploadées ($_FILES['images']) à la galerie d'un produit. */
function add_uploaded_images(int $productId): int
{
    if (empty($_FILES['images']['name'][0])) { return 0; }
    $pos = (int) Database::scalar('SELECT COALESCE(MAX(position), -1) + 1 FROM product_images WHERE product_id = ?', [$productId]);
    $count = 0;
    foreach ($_FILES['images']['name'] as $i => $n) {
        $file = [
            'name' => $_FILES['images']['name'][$i], 'type' => $_FILES['images']['type'][$i],
            'tmp_name' => $_FILES['images']['tmp_name'][$i], 'error' => $_FILES['images']['error'][$i],
            'size' => $_FILES['images']['size'][$i],
        ];
        $stored = store_product_image($file);
        if ($stored) {
            Database::insert('INSERT INTO product_images (product_id, file, position) VALUES (?,?,?)', [$productId, $stored, $pos++]);
            $count++;
        }
    }
    return $count;
}

if (is_post()) {
    csrf_check();
    $action = input('action', 'save');

    // --- Suppression d'une image existante ---
    if ($action === 'delete_image') {
        $img = Database::first('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int) input('image_id'), $id]);
        if ($img) {
            @unlink(UPLOADS_PATH . '/products/' . $img['file']);
            Database::run('DELETE FROM product_images WHERE id = ?', [$img['id']]);
            flash('Image supprimée.');
        }
        redirect('admin/product-edit.php?id=' . $id);
    }

    // --- Définir une image comme principale (position la plus basse) ---
    if ($action === 'set_primary') {
        $min = (int) Database::scalar('SELECT COALESCE(MIN(position), 0) FROM product_images WHERE product_id = ?', [$id]);
        Database::run('UPDATE product_images SET position = ? WHERE id = ? AND product_id = ?', [$min - 1, (int) input('image_id'), $id]);
        flash('Image principale mise à jour.');
        redirect('admin/product-edit.php?id=' . $id);
    }

    // --- Enregistrement du produit (texte + éventuelles images) ---
    $data = [
        'name'        => input('name'),
        'sku'         => input('sku') ?: 'BR-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)),
        'brand_id'    => (int) input('brand_id') ?: null,
        'category_id' => (int) input('category_id') ?: null,
        'short_desc'  => input('short_desc'),
        'long_desc'   => input('long_desc'),
        'ingredients' => input('ingredients'),
        'usage_tips'  => input('usage_tips'),
        'price'       => (float) input('price'),
        'old_price'   => input('old_price') !== '' ? (float) input('old_price') : null,
        'stock'       => (int) input('stock'),
        'weight'      => (int) input('weight'),
        'volume'      => input('volume'),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_new'      => isset($_POST['is_new']) ? 1 : 0,
        'is_bestseller'=> isset($_POST['is_bestseller']) ? 1 : 0,
        'on_sale'     => (input('old_price') !== '' && (float) input('old_price') > (float) input('price')) ? 1 : 0,
        'video_url'   => input('video_url') ?: null,
    ];

    if (mb_strlen($data['name']) < 2) {
        flash('Le nom du produit est requis.', 'error');
    } else {
        if ($isNew) {
            $data['slug'] = slugify($data['name']) . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            $cols = implode(',', array_keys($data));
            $ph = implode(',', array_fill(0, count($data), '?'));
            $newId = Database::insert("INSERT INTO products ($cols) VALUES ($ph)", array_values($data));
            $n = add_uploaded_images($newId);
            flash('Produit créé avec succès.' . ($n ? " $n image(s) ajoutée(s)." : ''));
            redirect('admin/product-edit.php?id=' . $newId);
        } else {
            $set = implode(',', array_map(fn($k) => "$k = ?", array_keys($data)));
            Database::run("UPDATE products SET $set WHERE id = ?", array_merge(array_values($data), [$id]));
            $n = add_uploaded_images($id);
            flash('Produit mis à jour.' . ($n ? " $n image(s) ajoutée(s)." : ''));
            redirect('admin/product-edit.php?id=' . $id);
        }
    }
}

$brands = Database::all('SELECT id, name FROM brands WHERE deleted_at IS NULL ORDER BY name');
$cats = Database::all('SELECT id, name, parent_id FROM categories WHERE deleted_at IS NULL ORDER BY position');
$images = $id ? Database::all('SELECT * FROM product_images WHERE product_id = ? ORDER BY position', [$id]) : [];
$v = fn($k, $d = '') => e($p[$k] ?? $d);

$adminActive = 'products';
$adminTitle = $isNew ? 'Nouveau produit' : 'Modifier : ' . $p['name'];
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<a href="<?php h(url('admin/products.php')); ?>" class="a-btn ghost sm" style="margin-bottom:16px"><?= icon('arrow','flip') ?> Retour</a>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <div class="a-grid-2">
    <div>
      <div class="a-panel">
        <h3 style="margin-bottom:16px">Informations générales</h3>
        <div class="a-field"><label>Nom du produit *</label><input name="name" required value="<?= $v('name') ?>"></div>
        <div class="a-row">
          <div class="a-field"><label>Référence (SKU)</label><input name="sku" value="<?= $v('sku') ?>" placeholder="Auto si vide"></div>
          <div class="a-field"><label>Volume / Contenance</label><input name="volume" value="<?= $v('volume') ?>" placeholder="50 ml"></div>
        </div>
        <div class="a-field"><label>Description courte</label><textarea name="short_desc" style="min-height:70px"><?= $v('short_desc') ?></textarea></div>
        <div class="a-field"><label>Description longue</label><textarea name="long_desc"><?= $v('long_desc') ?></textarea></div>
        <div class="a-field"><label>Composition (INCI)</label><textarea name="ingredients" style="min-height:70px"><?= $v('ingredients') ?></textarea></div>
        <div class="a-field"><label>Conseils d'utilisation</label><textarea name="usage_tips" style="min-height:70px"><?= $v('usage_tips') ?></textarea></div>
        <div class="a-field"><label>Vidéo (URL YouTube, facultatif)</label><input name="video_url" value="<?= $v('video_url') ?>"></div>
      </div>

      <div class="a-panel">
        <h3 style="margin-bottom:6px">Images du produit</h3>
        <p style="color:var(--a-muted);font-size:.85rem;margin:0 0 14px">Ajoutez une ou plusieurs photos (JPG, PNG, WebP, GIF · 5 Mo max). La première image sert de visuel principal.</p>
        <div class="a-field"><label>Ajouter des images</label><input type="file" name="images[]" accept="image/*" multiple></div>
        <p style="color:var(--a-muted);font-size:.78rem"><?= icon('shield') ?> Les images sont enregistrées lors de l'enregistrement du produit. Sans image, un visuel élégant est généré automatiquement.</p>
      </div>
    </div>

    <div>
      <div class="a-panel">
        <h3 style="margin-bottom:16px">Prix & Stock</h3>
        <div class="a-row">
          <div class="a-field"><label>Prix (DA) *</label><input type="number" step="0.01" name="price" required value="<?= $v('price') ?>"></div>
          <div class="a-field"><label>Ancien prix</label><input type="number" step="0.01" name="old_price" value="<?= $v('old_price') ?>"></div>
        </div>
        <div class="a-row">
          <div class="a-field"><label>Stock</label><input type="number" name="stock" value="<?= $v('stock', '0') ?>"></div>
          <div class="a-field"><label>Poids (g)</label><input type="number" name="weight" value="<?= $v('weight') ?>"></div>
        </div>
      </div>

      <div class="a-panel">
        <h3 style="margin-bottom:16px">Classification</h3>
        <div class="a-field"><label>Marque</label>
          <select name="brand_id"><option value="">—</option>
            <?php foreach ($brands as $b): ?><option value="<?= $b['id'] ?>" <?= ($p['brand_id'] ?? 0) == $b['id'] ? 'selected' : '' ?>><?php h($b['name']); ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="a-field"><label>Catégorie</label>
          <select name="category_id"><option value="">—</option>
            <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($p['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= $c['parent_id'] ? '— ' : '' ?><?php h($c['name']); ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="a-panel">
        <h3 style="margin-bottom:16px">Mise en avant</h3>
        <label style="display:flex;gap:10px;align-items:center;padding:6px 0"><input type="checkbox" name="is_featured" <?= !empty($p['is_featured'])?'checked':'' ?>> Produit vedette</label>
        <label style="display:flex;gap:10px;align-items:center;padding:6px 0"><input type="checkbox" name="is_new" <?= !empty($p['is_new'])?'checked':'' ?>> Nouveauté</label>
        <label style="display:flex;gap:10px;align-items:center;padding:6px 0"><input type="checkbox" name="is_bestseller" <?= !empty($p['is_bestseller'])?'checked':'' ?>> Meilleure vente</label>
      </div>

      <button class="a-btn" style="width:100%;justify-content:center;padding:14px"><?= icon('check') ?> <?= $isNew ? 'Créer le produit' : 'Enregistrer' ?></button>
    </div>
  </div>
</form>

<?php if (!$isNew): ?>
<!-- Galerie actuelle (formulaires indépendants : supprimer / définir principale) -->
<div class="a-panel" style="margin-top:22px">
  <div class="a-panel-head"><h3>Galerie actuelle (<?= count($images) ?>)</h3></div>
  <?php if (empty($images)): ?>
    <p class="a-empty">Aucune image pour le moment. Un visuel élégant est généré automatiquement. Ajoutez des photos ci-dessus.</p>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px">
      <?php foreach ($images as $i => $img): ?>
      <div style="border:1px solid var(--a-border);border-radius:12px;overflow:hidden;background:var(--a-bg)">
        <div style="aspect-ratio:1;position:relative">
          <img src="<?php h(url('uploads/products/' . $img['file'])); ?>" alt="" style="width:100%;height:100%;object-fit:cover">
          <?php if ($i === 0): ?><span class="a-pill st-delivered" style="position:absolute;top:8px;left:8px">Principale</span><?php endif; ?>
        </div>
        <div style="display:flex;gap:6px;padding:8px">
          <?php if ($i !== 0): ?>
          <form method="post" style="flex:1"><?= csrf_field() ?><input type="hidden" name="action" value="set_primary"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="image_id" value="<?= $img['id'] ?>"><button class="a-btn ghost sm" style="width:100%;justify-content:center" title="Définir comme principale"><?= icon('star') ?></button></form>
          <?php endif; ?>
          <form method="post" style="flex:<?= $i === 0 ? '1' : '0 0 auto' ?>" onsubmit="return confirm('Supprimer cette image ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_image"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="image_id" value="<?= $img['id'] ?>"><button class="a-btn danger sm" style="width:100%;justify-content:center"><?= icon('trash') ?></button></form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php admin_footer(); ?>
