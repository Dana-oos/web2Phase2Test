<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$host     = "localhost";
$user     = "root";
$password = "root";
$database = "nurish_db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



$recipeID = isset($_POST['recipeID']) && is_numeric($_POST['recipeID'])
    ? intval($_POST['recipeID']) : 0;
$comment  = trim($_POST['comment'] ?? '');
$userID   = intval($_SESSION['id']);

if ($recipeID <= 0 || $comment === '') {
    header("Location: viewRecipe.php?id=$recipeID");
    exit();
}

$stmt = $conn->prepare("INSERT INTO comment (recipeID, userID, comment, date) VALUES (?, ?, ?, CURDATE())");
$stmt->bind_param("iis", $recipeID, $userID, $comment);
$stmt->execute();
$stmt->close();

// c. Redirect back to the same recipe page
header("Location: viewRecipe.php?id=$recipeID");
exit();
