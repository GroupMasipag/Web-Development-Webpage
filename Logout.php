<?php
require_once 'bootstrap.php';
$_SESSION = [];
session_destroy();
redirect_to('Login.php');
?>
