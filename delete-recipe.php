<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$host     = "localhost";
$user     = "root";
$password = "root";
$database = "nurish db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id   = $_SESSION['id'];
$recipe_id = intval($_POST['id'] ?? 0);

if ($recipe_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid recipe ID']);
    exit();
}

// Verify the recipe belongs to this user
$stmt = $conn->prepare("SELECT photoFileName FROM recipe WHERE id = ? AND userID = ?");
$stmt->bind_param("ii", $recipe_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Recipe not found or unauthorized']);
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

// Delete all related records
$conn->query("DELETE FROM likes        WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM favourites   WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM comment      WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM report       WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM ingredients  WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM instructions WHERE recipeID = $recipe_id");

// Delete the recipe itself
$stmt = $conn->prepare("DELETE FROM recipe WHERE id = ? AND userID = ?");
$stmt->bind_param("ii", $recipe_id, $user_id);
$stmt->execute();
$stmt->close();

$conn->close();

// Return JSON response
echo json_encode(['success' => true]);
exit();
?>
