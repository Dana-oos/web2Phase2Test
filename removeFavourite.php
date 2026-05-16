<?php
session_start();

$conn = new mysqli("localhost", "root", "root", "nurish_db");

if (!isset($_SESSION['id']) || $_SESSION['userType'] !== 'user') {
    echo "false";
    exit();
}

$userID = (int) $_SESSION['id'];
$recipeID = (int) $_POST['recipeID'];

$sql = "DELETE FROM favourites 
        WHERE userID = $userID AND recipeID = $recipeID";

if ($conn->query($sql)) {
    echo "true";
} else {
    echo "false";
}
?>
