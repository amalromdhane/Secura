<?php
/**
 * Database Configuration
 * Contains connection logic for MySQL database
 */

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';

// Database names
$db_name_secura = 'secura_modules';  // For login/register
$db_name_cyber = 'secura_modules';   // For modules/courses (same DB for now)

/**
 * Get PDO database connection
 * @param string $database Database name ('secura' or 'cyber')
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(string $database = 'secura'): PDO {
    global $db_host, $db_user, $db_pass, $db_name_secura, $db_name_cyber;
    
    $db_name = ($database === 'cyber') ? $db_name_cyber : $db_name_secura;
    
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    return $pdo;
}