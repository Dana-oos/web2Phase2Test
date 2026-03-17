
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "root", "nurish db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$emailAddress = trim($_POST['emailAddress']);
$password = trim($_POST['password']);

// 1) Check if email is in blockeduser table
$checkBlocked = $conn->prepare("SELECT id FROM blockeduser WHERE emailAddress = ?");
if (!$checkBlocked) {
    die("Prepare failed for blocked user check: " . $conn->error);
}

$checkBlocked->bind_param("s", $emailAddress);
$checkBlocked->execute();
$checkBlocked->store_result();

if ($checkBlocked->num_rows > 0) {
    $checkBlocked->close();
    $conn->close();
    header("Location: login.html?error=blocked");
    exit();
}
$checkBlocked->close();

// 2) Check if email exists in user table
$checkUser = $conn->prepare("SELECT id, userType, password FROM user WHERE emailAddress = ?");
if (!$checkUser) {
    die("Prepare failed for user login check: " . $conn->error);
}

$checkUser->bind_param("s", $emailAddress);
$checkUser->execute();
$result = $checkUser->get_result();

if ($result->num_rows === 0) {
    $checkUser->close();
    $conn->close();
    header("Location: login.html?error=invalid");
    exit();
}

$user = $result->fetch_assoc();
$checkUser->close();


// 3) Check password
if (!password_verify($password, $user['password'])) {
    $conn->close();
    header("Location: login.html?error=invalid");
    exit();
}

// 4) Save session variables
$_SESSION['id'] = $user['id'];
$_SESSION['userType'] = $user['userType'];

$conn->close();

// 5) Redirect based on user type
if ($user['userType'] === 'admin') {
    header("Location: Admin.html");
    exit();
} else {
    header("Location: user.html");
    exit();
}
?>
