<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: my-recipes.php");
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

$user_id     = $_SESSION['id'];
$recipe_id   = intval($_POST['recipe_id']);
$recipe_name = trim($_POST['name']);
$category_id = intval($_POST['category_id']);
$description = trim($_POST['description']);
$video       = trim($_POST['video_url'] ?? '');

// Verify the recipe belongs to this user and get existing file info
$check = $conn->prepare("SELECT photoFileName, videoFilePath FROM recipe WHERE id = ? AND userID = ?");
$check->bind_param("ii", $recipe_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    die("❌ Unauthorized or recipe not found.");
}

$existing = $result->fetch_assoc();
$check->close();

// Handle photo  (replace if new file uploaded, otherwise keep existing)
if (!empty($_FILES['photo']['name'])) {
    $photoFileName = basename($_FILES['photo']['name']);
    $uploadDir     = "uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $target = $uploadDir . $photoFileName;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
        die("❌ Image upload failed. Check uploads/ folder permissions.");
    }
} else {
    // Keep existing photo
    $photoFileName = $existing['photoFileName'];
}

// Handle video (replace if new URL provided, otherwise keep existing)
if (!empty($video)) {
    $videoFilePath = $video;
} else {
    $videoFilePath = $existing['videoFilePath'];
}

// Update recipe
$stmt = $conn->prepare(
    "UPDATE recipe SET categoryID=?, name=?, description=?, photoFileName=?, videoFilePath=?
     WHERE id=? AND userID=?"
);

if (!$stmt) {
    die("❌ Prepare failed: " . $conn->error);
}

$stmt->bind_param("issssii", $category_id, $recipe_name, $description, $photoFileName, $videoFilePath, $recipe_id, $user_id);

if (!$stmt->execute()) {
    die("❌ Recipe update failed: " . $stmt->error);
}

$stmt->close();

// Delete old ingredients and instructions then reinsert new ones
$conn->query("DELETE FROM ingredients  WHERE recipeID = $recipe_id");
$conn->query("DELETE FROM instructions WHERE recipeID = $recipe_id");

// reinsert Ingredients
$ing_names      = $_POST['ingredient_name']     ?? [];
$ing_quantities = $_POST['ingredient_quantity'] ?? [];

$stmt = $conn->prepare(
    "INSERT INTO ingredients (recipeID, IngredientName, IngredientQuantity)
     VALUES (?, ?, ?)"
);

if (!$stmt) {
    die("❌ Prepare failed (ingredients): " . $conn->error);
}

for ($i = 0; $i < count($ing_names); $i++) {
    $ing_name = trim($ing_names[$i]);
    $ing_qty  = trim($ing_quantities[$i] ?? '');

    if (!empty($ing_name)) {
        $stmt->bind_param("iss", $recipe_id, $ing_name, $ing_qty);
        if (!$stmt->execute()) {
            die("❌ Ingredient insert failed: " . $stmt->error);
        }
    }
}

$stmt->close();

// reinsert Instructions
$steps = $_POST['instruction_step'] ?? [];

$stmt = $conn->prepare(
    "INSERT INTO instructions (recipeID, step, stepOrder)
     VALUES (?, ?, ?)"
);

if (!$stmt) {
    die("❌ Prepare failed (instructions): " . $conn->error);
}

$order = 1;
for ($i = 0; $i < count($steps); $i++) {
    $step = trim($steps[$i]);

    if (!empty($step)) {
        $stmt->bind_param("isi", $recipe_id, $step, $order);
        if (!$stmt->execute()) {
            die("❌ Instruction insert failed: " . $stmt->error);
        }
        $order++;
    }
}

$stmt->close();
$conn->close();

// Redirect to my-recipes page
header("Location: my-recipes.php");
exit();
?>
