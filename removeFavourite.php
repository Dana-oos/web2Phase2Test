<?php
session_start();



$host = "localhost";
$username = "root";
$password = "root";
$database = "nurish db";

$conn = new mysqli($host, $username, $password, $database);



/* تأكد أن المستخدم مسجل */ 
if (!isset($_SESSION['id']) || !isset($_SESSION['userType']) || $_SESSION['userType'] !== 'user') {
    header("Location: login.php");
    exit();
}

/* حذف من المفضلة */
if (isset($_POST['recipeID'])) {
    $recipeID = (int)$_POST['recipeID'];
    $userID = (int)$_SESSION['id'];

    $conn->query("DELETE FROM favourites WHERE userID=$userID AND recipeID=$recipeID");
}

/* يرجع لصفحة المستخدم */
header("Location: user.php");
exit();
?>