<?php
define('DB_SERVER', 'sql100.infinityfree.com');
define('DB_USERNAME', 'if0_40117326');
define('DB_PASSWORD', 'qFteVhPBdhvkXyE');
define('DB_NAME', 'if0_40117326_karaoke');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($conn === false){
    die("ERROR: Could not connect. " . $conn->connect_error);
}
?>