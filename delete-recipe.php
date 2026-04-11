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

$user_id   = $_SESSION['id'];
$recipe_id = intval($_GET['id'] ?? 0);

if ($recipe_id === 0) {
    header("Location: my-recipes.php");
    exit();
}

// Verify the recipe belongs to this user and get file info
$stmt = $conn->prepare("SELECT photoFileName, videoFilePath FROM recipe WHERE id = ? AND userID = ?");
$stmt->bind_param("ii", $recipe_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Recipe not found or doesn't belong to this user
    header("Location: my-recipes.php");
    exit();
}

$recipe = $result->fetch_assoc();
$stmt->close();

// Delete photo file from server
if (!empty($recipe['photoFileName'])) {
    $photoPath = "uploads/" . $recipe['photoFileName'];
    if (file_exists($photoPath)) {
        unlink($photoPath);
    }
}

// Delete video file from server if it's a local file (not a URL)
if (!empty($recipe['videoFilePath'])) {
    $video = $recipe['videoFilePath'];
    // Only delete if it's a local file path, not an external URL
    if (!filter_var($video, FILTER_VALIDATE_URL)) {
        if (file_exists($video)) {
            unlink($video);
        }
    }
}

// Delete all related records in  order
$conn->query("DELETE FROM likes        WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM favourites   WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM comment      WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM report       WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM ingredients  WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM instructions WHERE recipeID = $recipe_id");

//  delete the recipe itself
$stmt = $conn->prepare("DELETE FROM recipe WHERE id = ? AND userID = ?");
$stmt->bind_param("ii", $recipe_id, $user_id);
$stmt->execute();
$stmt->close();

$conn->close();

// Redirect back to my recipes
header("Location: my-recipes.php");
exit();
?>
