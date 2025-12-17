<?php
// backend/includes/db.php

// === IMPORTANT ===
// Replace with your actual InfinityFree database credentials.
define('DB_SERVER', 'sql100.infinityfree.com'); // Or your specific DB host
define('DB_USERNAME', 'if0_40117343');          // Your InfinityFree username
define('DB_PASSWORD', 'YOUR_DATABASE_PASSWORD'); // Your database password
define('DB_NAME', 'if0_40117343_karaoke');      // Your database name

// Data Source Name (DSN) for PDO
$dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4";

// PDO connection options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create the PDO database connection
    $conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
} catch (\PDOException $e) {
    // If connection fails, return a JSON error
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}
?>
