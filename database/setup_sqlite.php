<?php
// This script sets up the SQLite database and creates the necessary tables.
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_file = __DIR__ . '/karaoke.db';
$schema_file = __DIR__ . '/schema.sql';

try {
    // 1. Create or open the SQLite database
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>Successfully connected to the SQLite database at '{$db_file}'.</p>";

    // 2. Read the schema file
    $sql_schema = file_get_contents($schema_file);
    if ($sql_schema === false) {
        throw new Exception("Could not read schema.sql file.");
    }
     echo "<p>Read schema.sql file successfully.</p>";

    // 3. Execute the SQL to create tables
    $pdo->exec($sql_schema);
    echo "<p style='color:green;'>Database schema created successfully.</p>";

    echo "<h2>Database setup complete!</h2>";

} catch (Exception $e) {
    die("<p style='color:red;'><strong>Error:</strong> " . $e->getMessage() . "</p>");
}
?>
