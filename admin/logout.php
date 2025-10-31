<?php
declare(strict_types=1);
require_once 'auth.php';

logoutAdmin();
header('Location: login.php');
exit;
