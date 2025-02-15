<?php
// Database configuration
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'deepstateguide',
        'charset' => 'utf8mb4',
        'user' => getenv('DB_USER'),
        'pass' => getenv('DB_PASS'),
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ]
];