<?php
require 'inc.php';
need('student');

$u    = me();
$err  = '';
$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();

/* ================= Handle the report form ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    chk();

    $type  = $_POST['type'] === 'Found' ? 'Found' : 'Lost';
    $name  = clean($_POST['name'] ?? '', 100);
    $desc  = clean($_POST['description'] ?? '', 800);
    $loc   = clean($_POST['location'] ?? '', 120);
    $date  = $_POST['date'] ?? '';
    $cat   = (int)($_POST['category'] ?? 0);
    $photo = null;

    $d = DateTime::createFromFormat('Y-m-d', $date);

    $ok = db()->prepare('SELECT 1 FROM categories WHERE id=?');
    $ok->execute([$cat]);

    // --- Validate the text fields, date and category ---
    if (!$name || !$desc || !$loc) {
        $err = 'Please fill in every field.';

    } elseif (!$d || $d->format('Y-m-d') !== $date || $date > date('Y-m-d')) {
        $err = 'Enter a valid date that is not in the future.';

    } elseif (!$ok->fetch()) {
        $err = 'Choose a category.';

    // --- Validate and save the optional photo ---
    } elseif (!empty($_FILES['photo']['tmp_name'])) {
        $f    = $_FILES['photo'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext  = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ][$mime] ?? null;

        if ($f['error'] !== 0 || $f['size'] > 2 * 1024 * 1024 || !$ext) {
            $err = 'Photo must be a JPG, PNG or WebP under 2 MB.';
        } else {
            $photo = bin2hex(random_bytes(12)) . '.' . $ext;
            move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $photo);
        }
    }

    // --- Everything valid: save the report ---
    if (!$err) {
        db()->prepare('INSERT INTO items(user_id, category_id, name, description, location, event_date, photo, status) VALUES(?,?,?,?,?,?,?,?)')
            ->execute([$u['id'], $cat, $name, $desc, $loc, $date, $photo, $type]);

        logit('report_' . strtolower($type), $name);
        flash('Report submitted. It will appear in Browse once the administrator verifies it.');
        header('Location: my.php');
        exit;
    }
}

head('Report an item');
?>

<section class="card wide">
    <h1>Report an item</h1>

    <?php if ($err): ?>
        <div class="err"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?= tok() ?>

        <div class="seg">
            <label>
                <input type="radio" name="type" value="Lost" checked>
                <span>I lost something</span>
            </label>
            <label>
                <input type="radio" name="type" value="Found">
                <span>I found something</span>
            </label>
        </div>

        <div class="two">
            <label>Item name
                <input name="name" maxlength="100" required value="<?= e($_POST['name'] ?? '') ?>">
            </label>

            <label>Category
                <select name="category" required>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Where
                <input name="location" maxlength="120" required
                       placeholder="e.g. CEIT Building, Room 204"
                       value="<?= e($_POST['location'] ?? '') ?>">
            </label>

            <label>When
                <input type="date" name="date" max="<?= date('Y-m-d') ?>" required
                       value="<?= e($_POST['date'] ?? '') ?>">
            </label>
        </div>

        <label>Description
            <textarea name="description" maxlength="800" required
                      placeholder="Colour, brand, marks. For found items, leave out details only the owner would know."><?= e($_POST['description'] ?? '') ?></textarea>
        </label>

        <label>Photo (optional)
            <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp">
        </label>
        <img id="prev" class="prev" alt="Preview" hidden>

        <button class="btn">Submit report</button>
    </form>
</section>

<?php foot();