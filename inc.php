<?php
/* =====================================================
   Configuration: edit your database credentials here
   ===================================================== */
const DB_HOST = 'localhost';
const DB_NAME = 'lostfound';
const DB_USER = 'root';
const DB_PASS = '';

/* =====================================================
   Session and security headers
   ===================================================== */
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header(
    "Content-Security-Policy: default-src 'self'; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
    . "font-src https://fonts.gstatic.com; "
    . "img-src 'self' data: blob:; "   // blob: lets the photo preview work on the report form
    . "script-src 'self' 'unsafe-inline'"
);

/* =====================================================
   Database
   ===================================================== */
function db()
{
    static $p;
    return $p ??= new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
}

/* =====================================================
   Helpers: output, CSRF, auth, logging, flash messages
   ===================================================== */
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function csrf()
{
    return $_SESSION['t'] ??= bin2hex(random_bytes(16));
}

function tok()
{
    return '<input type="hidden" name="t" value="' . csrf() . '">';
}

function chk()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['t'] ?? '', $_POST['t'] ?? '')) {
        http_response_code(400);
        die('Invalid request token.');
    }
}

function me()
{
    return $_SESSION['u'] ?? null;
}

function need(...$roles)
{
    $u = me();

    if (!$u) {
        header('Location: login.php');
        exit;
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied.');
    }
}

function logit($a, $d = '')
{
    db()->prepare('INSERT INTO activity_logs(user_id, action, details, ip) VALUES(?,?,?,?)')
        ->execute([me()['id'] ?? null, $a, mb_substr($d, 0, 255), $_SERVER['REMOTE_ADDR'] ?? '']);
}

function flash($m = null)
{
    if ($m !== null) {
        $_SESSION['f'] = $m;
        return;
    }
    $x = $_SESSION['f'] ?? '';
    unset($_SESSION['f']);
    return $x;
}

function tno($id)
{
    return 'LF-' . str_pad($id, 4, '0', STR_PAD_LEFT);
}

function clean($s, $max)
{
    return mb_substr(trim(strip_tags((string)$s)), 0, $max);
}

function badge($s)
{
    return '<span class="bd ' . e($s) . '">' . e($s) . '</span>';
}

/* =====================================================
   Page layout: header and footer
   ===================================================== */
function head($title, $print = false)
{
    $u   = me();
    $cur = basename($_SERVER['SCRIPT_NAME']);
    $n   = fn($f, $l) => '<a href="' . $f . '"' . ($cur === $f ? ' class="on"' : '') . '>' . $l . '</a>';

    echo '<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>' . e($title) . ' · CvSU Lost &amp; Found</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=Figtree:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>';

    if ($u) {
        echo '
<header class="top">
    <a class="brand" href="index.php"><i></i>Lost &amp; Found <small>CvSU Indang</small></a>
    <nav>' . $n('index.php', 'Browse');

        if ($u['role'] === 'student') {
            echo $n('report.php', 'Report an item') . $n('my.php', 'My activity');
        }
        if ($u['role'] === 'admin') {
            echo $n('admin.php', 'Admin');
        }

        echo '</nav>
    <form method="post" action="logout.php" class="who">
        <span>' . e($u['name']) . ' · ' . e($u['role']) . '</span>'
        . tok() . '
        <button class="btn ghost">Log out</button>
    </form>
</header>';
    }

    echo '
<main>';

    if ($f = flash()) {
        echo '<div class="note">' . e($f) . '</div>';
    }
}

function foot()
{
    echo '
</main>
<script>
    // Ask for confirmation on buttons with data-confirm
    document.querySelectorAll("[data-confirm]").forEach(b =>
        b.addEventListener("click", x => { if (!confirm(b.dataset.confirm)) x.preventDefault(); })
    );

    // Auto-submit a form when a select with data-auto changes
    document.querySelectorAll("[data-auto]").forEach(s =>
        s.addEventListener("change", () => s.form.submit())
    );

    // Photo preview on the report form
    var p = document.getElementById("photo");
    if (p) p.addEventListener("change", () => {
        var f = p.files[0], i = document.getElementById("prev");
        if (f) { i.src = URL.createObjectURL(f); i.hidden = false; }
    });
</script>
</body>
</html>';
}