<?php
// IMPORTANT: This file should be removed after installation.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials - Adjust if necessary
define('DB_SERVER', 'sql100.infinityfree.com');
define('DB_USERNAME', 'if0_40117326');
define('DB_PASSWORD', 'qFteVhPBdhvkXyE');
define('DB_NAME', 'if0_40117326_karaoke');


// Admin user credentials - Change these!
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'password123');


echo "<h1>Karaoke System Installation</h1>";

// --- 1. Connect to MySQL Server ---
echo "<p>Attempting to connect to MySQL server...</p>";
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);
if ($conn->connect_error) {
    die("<p style='color:red;'><strong>Connection failed:</strong> " . $conn->connect_error . "</p>");
}
echo "<p style='color:green;'>Connected successfully to MySQL server.</p>";


// --- 2. Create Database ---
echo "<p>Attempting to create database '<code>" . DB_NAME . "</code>'...</p>";
$sql_create_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql_create_db) === TRUE) {
    echo "<p style='color:green;'>Database '<code>" . DB_NAME . "</code>' created or already exists.</p>";
} else {
    die("<p style='color:red;'><strong>Error creating database:</strong> " . $conn->error . "</p>");
}
$conn->select_db(DB_NAME);


// --- 3. Create Tables from schema.sql ---
echo "<p>Attempting to create tables from <code>schema.sql</code>...</p>";
$sql_schema = file_get_contents(__DIR__ . '/schema.sql');
if ($sql_schema === false) {
    die("<p style='color:red;'><strong>Error:</strong> Could not read <code>schema.sql</code>.</p>");
}
if ($conn->multi_query($sql_schema)) {
    // Consume multi-query results
    while ($conn->next_result()) {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }
    echo "<p style='color:green;'>Tables created successfully.</p>";
} else {
    // Don't die here, as tables might already exist. Check specifically for that.
     if (strpos($conn->error, "already exists") !== false) {
        echo "<p style='color:orange;'>Tables already exist, skipping creation.</p>";
    } else {
        die("<p style='color:red;'><strong>Error creating tables:</strong> " . $conn->error . "</p>");
    }
}

// --- 3.5. Apply Migrations ---
echo "<p>Attempting to apply migrations from <code>migrations.sql</code>...</p>";
$sql_migrations = file_get_contents(__DIR__ . '/migrations.sql');
if ($sql_migrations === false) {
    die("<p style='color:red;'><strong>Error:</strong> Could not read <code>migrations.sql</code>.</p>");
}
// Clear any previous results before running a new multi_query
while ($conn->more_results() && $conn->next_result()) {
    // free stored result sets
    if ($result = $conn->store_result()) {
        $result->free();
    }
}
if ($conn->multi_query($sql_migrations)) {
    // Consume multi-query results
    while ($conn->next_result()) {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }
    echo "<p style='color:green;'>Migrations applied successfully.</p>";
} else {
    if (strpos($conn->error, "already exists") !== false) {
        echo "<p style='color:orange;'>Migration tables already exist, skipping creation.</p>";
    } else {
         die("<p style='color:red;'><strong>Error applying migrations:</strong> " . $conn->error . "</p>");
    }
}


// --- 4. Create Admin User ---
echo "<p>Attempting to create admin user '<code>" . ADMIN_USERNAME . "</code>'...</p>";
$username = ADMIN_USERNAME;
$password_hash = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);

// Use a prepared statement to prevent SQL injection and handle existing users
$stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt_check->bind_param("s", $username);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    echo "<p style='color:orange;'>Admin user '<code>" . $username . "</code>' already exists.</p>";
} else {
    $stmt_insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt_insert->bind_param("ss", $username, $password_hash);
    if ($stmt_insert->execute()) {
        echo "<p style='color:green;'>Admin user '<code>" . $username . "</code>' created successfully.</p>";
        echo "<p><strong>Username:</strong> " . ADMIN_USERNAME . "</p>";
        echo "<p><strong>Password:</strong> " . ADMIN_PASSWORD . " (Please change this after logging in!)</p>";
    } else {
        echo "<p style='color:red;'><strong>Error creating admin user:</strong> " . $stmt_insert->error . "</p>";
    }
    $stmt_insert->close();
}
$stmt_check->close();


// --- 5. Finalization ---
echo "<h2>Installation Complete!</h2>";
echo "<p style='color:red; font-weight:bold;'>IMPORTANT: Please delete this '<code>install.php</code>' file from your server for security reasons.</p>";
echo "<a href='../backend/admin/'>Go to Admin Login</a>";

$conn->close();
?>