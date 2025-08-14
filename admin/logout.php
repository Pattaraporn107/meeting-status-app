<?php
require __DIR__.'/../app/auth.php';
logout_admin();
header('Location: /admin/login.php');
exit;
