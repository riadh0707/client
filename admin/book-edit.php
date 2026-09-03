<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_once INCLUDES_PATH . '/pdf.php';
require_once INCLUDES_PATH . '/pptx.php';
require_admin();

$id = (int) input('id');
$book = $id ? Database::first('SELECT * FROM books WHERE id = ?', [$id]) : null;
$isNew = !$book;
$notice = '';

// Upload trop volumineux : PHP vide $_POST → message clair plutôt qu'erreur CSRF.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $limit = ini_get('post_max_size');
    flash('Fichier trop volumineux pour le serveur (limite ' . $limit . '). Réduisez le fichier ou augmentez upload_max_filesize / post_max_size.', 'error');
    redirect('admin/book-edit.php' . ($id ? '?id=' . $id : ''));
}

if (is_post()) {
    csrf_check();
    $title = input('title');
    $data = [
        'title'         => $title,
        'author'        => input('author') ?: 'La Bibliothèque',
        'sku'           => input('sku') ?: 'LIV-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)),
        'category_id'   => (int) input('category_id') ?: null,
        'short_desc'    => input('short_desc'),
        'long_desc'     => input('long_desc'),
        'toc'           => input('toc'),
        'price'         => (float) input('price'),
        'old_price'     => input('old_price') !== '' ? (float) input('old_price') : null,
        'preview_pages' => max(1, (int) input('preview_pages')),
        'language'      => input('language') ?: 'Français',
        'is_featured'   => isset($_POST['is_featured']) ? 1 : 0,
        'is_new'        => isset($_POST['is_new']) ? 1 : 0,
        'is_bestseller' => isset($_POST['is_bestseller']) ? 1 : 0,
        'on_sale'       => (input('old_price') !== '' && (float) input('old_price') > (float) input('price')) ? 1 : 0,
    ];

    if (mb_strlen($title) < 2) {
        flash('Le titre est requis.', 'error');
    } else {
        // ---- Upload couverture (image) ----
        $coverName = $book['cover_image'] ?? null;
        if (!empty($_FILES['cover']['name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && $_FILES['cover']['size'] < 4 * 1024 * 1024) {
                $coverName = slugify($title) . '-cover-' . bin2hex(random_bytes(2)) . '.' . $ext;
                @move_uploaded_file($_FILES['cover']['tmp_name'], UPLOADS_PATH . '/covers/' . $coverName);
            }
        }

        // ---- Upload du fichier principal : PDF ou présentation PowerPoint ----
        $fileName     = $book['pdf_file'] ?? null;
        $previewName  = $book['preview_file'] ?? null;
        $fileType     = $book ? book_format($book) : 'pdf';
        $pages        = (int) ($book['pages_count'] ?? 0);
        $fileUploaded = false;

        if (!empty($_FILES['pdf']['name']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            $ext    = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
            $maxMb  = (int) ($GLOBALS['config']['uploads']['max_pdf_mb'] ?? 40);
            $isPdf  = $ext === 'pdf';
            $isPptx = in_array($ext, ['pptx', 'ppt'], true);

            if (!$isPdf && !$isPptx) {
                $notice = 'Fichier ignoré : formats acceptés PDF, PPTX et PPT.';
            } elseif ($_FILES['pdf']['size'] >= $maxMb * 1024 * 1024) {
                $notice = 'Fichier ignoré (taille supérieure à ' . $maxMb . ' Mo).';
            } else {
                $newName = slugify($title) . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
                $stored  = UPLOADS_PATH . '/pdf/' . $newName;
                if (!@move_uploaded_file($_FILES['pdf']['tmp_name'], $stored)) {
                    $notice = 'Le fichier n\'a pas pu être enregistré (dossier uploads/pdf inscriptible ?).';
                } elseif ($isPptx && ($slideCount = pptx_slide_count($stored)) === 0) {
                    // Présentation illisible : on conserve le fichier existant.
                    @unlink($stored);
                    $notice = 'Présentation ignorée : le fichier n\'a pas pu être ouvert (.pptx attendu).';
                } else {
                    // Le fichier remplacé est supprimé pour ne pas saturer le quota disque.
                    $old = $book['pdf_file'] ?? null;
                    if ($old && basename($old) !== $newName && is_file(UPLOADS_PATH . '/pdf/' . basename($old))) {
                        @unlink(UPLOADS_PATH . '/pdf/' . basename($old));
                    }
                    $fileName     = $newName;
                    $fileUploaded = true;
                    $fileType     = $isPptx ? 'pptx' : 'pdf';
                    $pages        = $isPptx ? $slideCount : pdf_page_count($stored);
                    if ($isPptx) { $previewName = null; }  // aperçu rendu à la volée
                }
            }
        }

        // ---- (Re)génération de l'aperçu PDF (les présentations n'en ont pas besoin) ----
        $needPreview = $fileUploaded || (!$isNew && $book['preview_pages'] != $data['preview_pages']);
        if ($fileType === 'pdf' && $fileName && $needPreview) {
            $previewName = pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
            $ok = pdf_make_preview(UPLOADS_PATH . '/pdf/' . $fileName, UPLOADS_PATH . '/previews/' . $previewName, $data['preview_pages']);
            if (!$ok) { $notice = ($notice ? $notice . ' ' : '') . 'Aperçu non généré automatiquement (le PDF pourra servir d\'aperçu limité).'; }
        }

        $data['cover_image']  = $coverName;
        $data['file_type']    = $fileType;
        $data['pdf_file']     = $fileName;
        $data['preview_file'] = $fileType === 'pdf' ? $previewName : null;
        $data['pages_count']  = $pages;

        if ($isNew) {
            $data['slug'] = slugify($title) . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            $data['status'] = 'active';
            $cols = implode(',', array_keys($data));
            $ph = implode(',', array_fill(0, count($data), '?'));
            $newId = Database::insert("INSERT INTO books ($cols) VALUES ($ph)", array_values($data));
            flash('Titre créé.' . ($notice ? ' ' . $notice : ''));
            redirect('admin/book-edit.php?id=' . $newId);
        } else {
            $set = implode(',', array_map(fn($k) => "$k = ?", array_keys($data)));
            Database::run("UPDATE books SET $set WHERE id = ?", array_merge(array_values($data), [$id]));
            flash('Titre mis à jour.' . ($notice ? ' ' . $notice : ''));
            redirect('admin/book-edit.php?id=' . $id);
        }
    }
}

$cats = Database::all('SELECT id, name FROM categories WHERE deleted_at IS NULL ORDER BY position');
$v = fn($k, $d = '') => e($book[$k] ?? $d);
$hasFile    = $book && $book['pdf_file'] && is_file(UPLOADS_PATH . '/pdf/' . $book['pdf_file']);
$bookIsPptx = $book ? book_is_slides($book) : false;
$maxUploadMb = (int) ($GLOBALS['config']['uploads']['max_pdf_mb'] ?? 40);

$adminActive = 'books';
$adminTitle = $isNew ? 'Nouveau titre' : 'Modifier : ' . $book['title'];
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<a href="<?php h(url('admin/books.php')); ?>" class="a-btn ghost sm" style="margin-bottom:16px"><?= icon('arrow','flip') ?> Retour</a>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="a-grid-2">
    <div>
      <div class="a-panel">
        <h3 style="margin-bottom:16px">Informations</h3>
        <div class="a-field"><label>Titre *</label><input name="title" required value="<?= $v('title') ?>"></div>
        <div class="a-row">
          <div class="a-field"><label>Auteur</label><input name="author" value="<?= $v('author', 'La Bibliothèque') ?>"></div>
          <div class="a-field"><label>Langue</label><input name="language" value="<?= $v('language', 'Français') ?>"></div>
        </div>
        <div class="a-field"><label>Description courte</label><textarea name="short_desc" style="min-height:70px"><?= $v('short_desc') ?></textarea></div>
        <div class="a-field"><label>Description longue</label><textarea name="long_desc"><?= $v('long_desc') ?></textarea></div>
        <div class="a-field"><label>Sommaire (une ligne par entrée)</label><textarea name="toc" style="min-height:120px" placeholder="Introduction&#10;Chapitre 1 : ...&#10;Chapitre 2 : ..."><?= $v('toc') ?></textarea></div>
      </div>
    </div>

    <div>
      <div class="a-panel">
        <h3 style="margin-bottom:16px">Fichier &amp; aperçu</h3>
        <?php if ($hasFile): ?>
          <div class="a-alert" style="margin-bottom:14px"><?= icon('check') ?>
            <?= $bookIsPptx ? 'Présentation PowerPoint présente' : 'PDF présent' ?> :
            <?= (int) $book['pages_count'] ?> <?= e(book_unit($book)) ?>.
            <a href="<?php h(url('lire.php?slug=' . $book['slug'])); ?>" target="_blank" rel="noopener" style="text-decoration:underline">Ouvrir le lecteur</a>
          </div>
        <?php endif; ?>
        <div class="a-field">
          <label>Fichier du titre : PDF ou PowerPoint <?= $isNew ? '' : '(laisser vide pour conserver)' ?></label>
          <input type="file" name="pdf" accept=".pdf,.pptx,.ppt,application/pdf,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.ms-powerpoint">
          <small style="color:var(--a-muted)">Formats acceptés : <strong>.pdf</strong>, <strong>.pptx</strong>, <strong>.ppt</strong>, <?= $maxUploadMb ?> Mo maximum. Les PDF s'ouvrent dans la visionneuse ; les présentations sont restituées en diapositives lisibles dans le navigateur.</small>
        </div>
        <div class="a-field"><label>Pages / diapositives d'aperçu gratuites</label><input type="number" name="preview_pages" min="1" value="<?= $v('preview_pages', setting('default_preview_pages', '10')) ?>"><small style="color:var(--a-muted)">Nombre de pages (PDF) ou de diapositives (PowerPoint) lisibles gratuitement. L'aperçu PDF est régénéré automatiquement.</small></div>
        <div class="a-field"><label>Image de couverture (facultatif)</label><input type="file" name="cover" accept="image/*"><small style="color:var(--a-muted)">Sinon, une couverture élégante est générée automatiquement.</small></div>
      </div>

      <div class="a-panel">
        <h3 style="margin-bottom:16px">Prix & rayon</h3>
        <div class="a-row">
          <div class="a-field"><label>Prix (DA) *</label><input type="number" step="1" name="price" required value="<?= $v('price', '2000') ?>"></div>
          <div class="a-field"><label>Ancien prix (promo)</label><input type="number" step="1" name="old_price" value="<?= $v('old_price') ?>"></div>
        </div>
        <div class="a-field"><label>Rayon</label><select name="category_id"><option value="">Aucun</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($book['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?php h($c['name']); ?></option><?php endforeach; ?></select></div>
        <div class="a-field"><label>Référence</label><input name="sku" value="<?= $v('sku') ?>" placeholder="Auto si vide"></div>
      </div>

      <div class="a-panel">
        <h3 style="margin-bottom:12px">Mise en avant</h3>
        <label style="display:flex;gap:10px;align-items:center;padding:6px 0"><input type="checkbox" name="is_featured" <?= !empty($book['is_featured'])?'checked':'' ?>> Titre vedette</label>
        <label style="display:flex;gap:10px;align-items:center;padding:6px 0"><input type="checkbox" name="is_new" <?= !empty($book['is_new'])?'checked':'' ?>> Nouveauté</label>
        <label style="display:flex;gap:10px;align-items:center;padding:6px 0"><input type="checkbox" name="is_bestseller" <?= !empty($book['is_bestseller'])?'checked':'' ?>> Meilleure vente</label>
      </div>

      <button class="a-btn" style="width:100%;justify-content:center;padding:14px"><?= icon('check') ?> <?= $isNew ? 'Créer le titre' : 'Enregistrer' ?></button>
    </div>
  </div>
</form>
<?php admin_footer(); ?>
