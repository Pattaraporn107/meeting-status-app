<?php
declare(strict_types=1);

$config = require __DIR__.'/config.php';
session_name($config['session_name']);
session_start();

function require_login(): void {
  if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
  }
}

function current_admin(): ?array {
  if (empty($_SESSION['admin_id'])) return null;
  return [
    'id' => $_SESSION['admin_id'],
    'username' => $_SESSION['admin_username'],
    'name' => $_SESSION['admin_name'],
  ];
}

function login_admin(array $admin): void {
  $_SESSION['admin_id'] = $admin['id'];
  $_SESSION['admin_username'] = $admin['username'];
  $_SESSION['admin_name'] = $admin['name'];
}

function logout_admin(): void {
  $_SESSION = [];
  session_destroy();
}
