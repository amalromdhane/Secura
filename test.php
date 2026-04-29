<?php
// Simple test to verify PHP is working
echo "PHP is working!<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP version: " . phpversion() . "<br>";

// Test database connection
require_once 'api/config.php';
if ($conn->connect_error) {
    echo "Database connection FAILED: " . $conn->connect_error;
} else {
    echo "Database connection SUCCESS!";
}
?>
