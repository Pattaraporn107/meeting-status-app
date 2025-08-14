<?php
declare(strict_types=1);

$config = require __DIR__.'/config.php';
date_default_timezone_set($config['timezone']);

function db(): PDO {
  static $pdo;
  if ($pdo) return $pdo;

  $c = require __DIR__.'/config.php';
  $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $c['db']['host'], $c['db']['port'], $c['db']['dbname'], $c['db']['charset']
  );
  $pdo = new PDO($dsn, $c['db']['user'], $c['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
  return $pdo;
}
