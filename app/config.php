<?php
declare(strict_types=1);

return [
  'db' => [
    'host' => 'db',
    'port' => 3306,
    'dbname' => getenv('MYSQL_DATABASE') ?: 'meeting_app',
    'user' => getenv('MYSQL_USER') ?: 'meeting_user',
    'pass' => getenv('MYSQL_PASSWORD') ?: 'changeme_user',
    'charset' => 'utf8mb4',
  ],
  'session_name' => 'meeting_admin',
  'timezone' => 'Asia/Bangkok'
];
