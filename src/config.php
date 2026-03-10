<?php
// Autoloading semplice o inclusioni manuali per ora
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';

use Lib\Database;

try {
    $dbInstance = Database::getInstance();
    $mysqli = $dbInstance->getConnection();
} catch (Exception $e) {
    die("Errore irreversibile: " . $e->getMessage());
}
