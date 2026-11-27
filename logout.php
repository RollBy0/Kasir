<?php
/** Keluar dari sesi. */
require __DIR__ . '/includes/init.php';

if (is_post()) {
    verify_csrf();
    $_SESSION = [];
    session_destroy();
}
redirect('index.php');
