<?php
$session_path = __DIR__ . DIRECTORY_SEPARATOR . 'sessions';

if (!is_dir($session_path)) {
    mkdir($session_path, 0755, true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_save_path($session_path);
}
?>
