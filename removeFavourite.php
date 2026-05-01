<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$host = "localhost";
$username = "root";
$password = "root";
$database = "nurish_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* تأكد أن المستخدم مسجل */
if (!isset($_SESSION['id']) || !isset($_SESSION['userType']) || $_SESSION['userType'] !== 'user') {
    die("Session problem");
}

/* حذف من المفضلة */
if (isset($_POST['recipeID'])) {
    $recipeID = (int)$_POST['recipeID'];
    $userID = (int)$_SESSION['id'];

    $sql = "DELETE FROM favourites WHERE userID = $userID AND recipeID = $recipeID";

    if (!$conn->query($sql)) {
        die("Delete error: " . $conn->error);
    }
} else {
    die("recipeID not received");
}

header("Location: user.php");
exit();
?>