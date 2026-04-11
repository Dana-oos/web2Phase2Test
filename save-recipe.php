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
    header("Location: add-recipe.php");
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
$recipe_name = trim($_POST['name']);
$category_id = intval($_POST['category_id']);
$description = trim($_POST['description']);
$video       = trim($_POST['video_url'] ?? '');

// image upload
if (empty($_FILES['photo']['name'])) {
    die("❌ ERROR: No photo uploaded.");
}

$photoFileName = basename($_FILES['photo']['name']);
$uploadDir     = "uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$target = $uploadDir . $photoFileName;

if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
    die("❌ ERROR: Image upload failed. Check uploads/ folder permissions.");
}

// Insert recipe
$stmt = $conn->prepare(
    "INSERT INTO recipe (userID, categoryID, name, description, photoFileName, videoFilePath)
     VALUES (?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    die("❌ Prepare failed: " . $conn->error);
}

$stmt->bind_param("iissss", $user_id, $category_id, $recipe_name, $description, $photoFileName, $video);

if (!$stmt->execute()) {
    die("❌ Recipe insert failed: " . $stmt->error);
}

$recipe_id = $conn->insert_id;
$stmt->close();

// Insert ingredients
$ing_names      = $_POST['ingredient_name']     ?? [];
$ing_quantities = $_POST['ingredient_quantity'] ?? [];

$stmt = $conn->prepare(
    "INSERT INTO ingredients (recipeID, ingredientName, ingredientQuantity)
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

// Insert instructions
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
