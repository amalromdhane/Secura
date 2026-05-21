<?php
$servername = '127.0.0.1';
$dbname     = 'secura_modules';
$db_user    = 'root';
$db_pass    = '';

$db_name_secura = $dbname;
$db_name_cyber  = $dbname;

/**
 * @param string $database 'secura' ou 'cyber'
 */
function getDBConnection(string $database = 'secura'): PDO
{
    global $servername, $db_user, $db_pass, $db_name_secura, $db_name_cyber;

    $db = ($database === 'cyber') ? $db_name_cyber : $db_name_secura;

    $pdo = new PDO(
        "mysql:host={$servername};dbname={$db};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    return $pdo;
}
