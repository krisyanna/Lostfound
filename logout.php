<?php
require 'inc.php';

// Only log out on a POST request that carries a valid CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    chk();
    logit('logout');
    $_SESSION = [];
    session_destroy();
}

header('Location: login.php');