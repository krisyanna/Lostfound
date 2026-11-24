<?php
require 'inc.php';
need('student');
chk();

$u     = me();
$id    = (int)($_POST['item'] ?? 0);
$proof = clean($_POST['proof'] ?? '', 600);

/* The item must be a verified, Found item that the student did not report themselves */
$s = db()->prepare("SELECT * FROM items WHERE id=? AND status='Found' AND verified=1 AND user_id<>?");
$s->execute([$id, $u['id']]);

if (!$s->fetch() || strlen($proof) < 15) {
    flash('That item cannot be claimed.');
    header('Location: index.php');
    exit;
}

/* Block duplicate pending claims on the same item */
$d = db()->prepare("SELECT 1 FROM claims WHERE item_id=? AND user_id=? AND status='Pending'");
$d->execute([$id, $u['id']]);

if ($d->fetch()) {
    flash('You already have a pending claim for this item.');
} else {
    db()->prepare('INSERT INTO claims(item_id, user_id, proof) VALUES(?,?,?)')
        ->execute([$id, $u['id'], $proof]);
    logit('claim_submitted', tno($id));
    flash('Claim sent. The administrator will verify your answer.');
}

header('Location: my.php');