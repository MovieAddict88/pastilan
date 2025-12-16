<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && !empty($_POST['id'])) {
    $id = trim($_POST['id']);

    $sql = "DELETE FROM songs WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        if ($stmt->execute([$id])) {
            header("location: dashboard.php");
            exit();
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
}
?>
