<?php
session_start();

header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "root";
$database = "nurish db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}

$categoryID = $_POST['categoryID'] ?? 'all';

$where = "";
if ($categoryID !== "all") {
    $categoryID = (int)$categoryID;
    $where = "WHERE recipe.categoryID = $categoryID";
}

$query = "
SELECT 
    recipe.id,
    recipe.name,
    recipe.photoFileName,
    user.firstName,
    user.photoFileName AS userPhoto,
    recipecategory.categoryName,
    COUNT(likes.recipeID) AS likesCount
FROM recipe
JOIN user ON recipe.userID = user.id
JOIN recipecategory ON recipe.categoryID = recipecategory.id
LEFT JOIN likes ON recipe.id = likes.recipeID
$where
GROUP BY recipe.id, recipe.name, recipe.photoFileName, user.firstName, user.photoFileName, recipecategory.categoryName
ORDER BY recipe.id DESC
";

$result = $conn->query($query);

$recipes = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recipes[] = $row;
    }
}

echo json_encode($recipes);
$conn->close();
?>