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

$recipeID = isset($_GET['recipeID']) && is_numeric($_GET['recipeID'])
    ? intval($_GET['recipeID']) : 0;
$userID   = intval($_SESSION['id']);



// Check not already favourited
$check = $conn->prepare("SELECT 1 FROM favourites WHERE userID = ? AND recipeID = ?");
$check->bind_param("ii", $userID, $recipeID);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO favourites (userID, recipeID) VALUES (?, ?)");
    $stmt->bind_param("ii", $userID, $recipeID);
    $stmt->execute();
    $stmt->close();
}
$check->close();

header("Location: viewRecipe.php?id=$recipeID");
exit();
