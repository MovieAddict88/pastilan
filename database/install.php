<?php
// database/install.php

echo "<h1>Karaoke System Installation</h1>";

// Include the database configuration, but handle errors gracefully
try {
    // We need to define the constants here because db.php will be included
    // and expects them to exist. However, we will create our own connection
    // for the installation process.
    define('DB_SERVER', 'YOUR_DATABASE_HOST');
    define('DB_USERNAME', 'YOUR_DATABASE_USERNAME');
    define('DB_PASSWORD', 'YOUR_DATABASE_PASSWORD');
    define('DB_NAME', 'YOUR_DATABASE_NAME');

    require_once '../backend/includes/db.php';
    echo "<p>Database configuration loaded.</p>";
} catch (Exception $e) {
    die("<p style='color:red;'>Error loading database configuration: " . $e->getMessage() . "</p>");
}

try {
    echo "<p>Attempting to connect to the database server...</p>";
    // Use the DSN from db.php to connect
    $dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>Successfully connected to the database.</p>";

    echo "<p>Reading schema file...</p>";
    $schema_path = __DIR__ . '/schema.sql';
    $schema = file_get_contents($schema_path);
    if ($schema === false) {
        throw new Exception("Could not read schema.sql file.");
    }
    echo "<p>Schema file read successfully.</p>";

    echo "<p>Executing schema...</p>";
    $conn->exec($schema);
    echo "<p style='color:green;'>Database tables created successfully.</p>";

    echo "<p>Creating default admin user...</p>";
    $admin_user = 'admin';
    $admin_pass = 'password'; // Default password
    $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password) ON DUPLICATE KEY UPDATE password = :password");
    $stmt->execute([':username' => $admin_user, ':password' => $hashed_pass]);
    echo "<p style='color:green;'>Default admin user created successfully.</p>";
    echo "<p style='color:orange; font-weight:bold;'>IMPORTANT: The default login is username 'admin' and password 'password'. Please change this password immediately after logging in.</p>";

    echo "<h2>Installation Complete!</h2>";
    echo "<p>You can now use the application.</p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Installation failed: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>An error occurred: " . $e->getMessage() . "</p>";
}
?>
