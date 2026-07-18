<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';
logout_now();
header('Location: login.php');
exit;