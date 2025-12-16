<?php
// setup_sqlite.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_file = __DIR__ . '/karaoke.db';
$schema_file = __DIR__ . '/schema.sql';

try {
    // Create a new SQLite database connection
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>Successfully connected to SQLite database.</p>";

    // Read the schema file
    $sql_schema = file_get_contents($schema_file);
    if ($sql_schema === false) {
        die("<p style='color:red;'><strong>Error:</strong> Could not read <code>schema.sql</code>.</p>");
    }

    // Execute the schema to create tables
    $pdo->exec($sql_schema);
    echo "<p style='color:green;'>Tables created successfully from <code>schema.sql</code>.</p>";

} catch (PDOException $e) {
    die("<p style='color:red;'><strong>Database error:</strong> " . $e->getMessage() . "</p>");
}
