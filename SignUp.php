
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: signup.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "root", "nurish db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$firstName = trim($_POST['firstName']);
$lastName = trim($_POST['lastName']);
$emailAddress = trim($_POST['emailAddress']);
$password = $_POST['password'];

// Default user type
$userType = "user";

// 1) Check if email already exists in user table
$checkUser = $conn->prepare("SELECT id FROM user WHERE emailAddress = ?");
if (!$checkUser) {
    die("Prepare failed for user check: " . $conn->error);
}
$checkUser->bind_param("s", $emailAddress);
$checkUser->execute();
$checkUser->store_result();

if ($checkUser->num_rows > 0) {
    $checkUser->close();
    $conn->close();
    header("Location: signup.html?error=exists");
    exit();
}
$checkUser->close();

// 2) Check if email exists in blockeduser table
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
    header("Location: signup.html?error=blocked");
    exit();
    }
$checkBlocked->close();

// 3) Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 4) Handle photo upload
$photoFileName = "default.png";

if (isset($_FILES['photoFileName']) && $_FILES['photoFileName']['error'] === 0) {
    $uploadDir = "uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = basename($_FILES['photoFileName']['name']);
    $photoFileName = uniqid() . "_" . $originalName;
    $targetFile = $uploadDir . $photoFileName;

    if (!move_uploaded_file($_FILES['photoFileName']['tmp_name'], $targetFile)) {
        die("Image upload failed.");
    }
}

// 5) Insert user into database
$insert = $conn->prepare("INSERT INTO user (userType, firstName, lastName, emailAddress, password, photoFileName) VALUES (?, ?, ?, ?, ?, ?)");
if (!$insert) {
    die("Prepare failed for insert: " . $conn->error);
}
$insert->bind_param("ssssss", $userType, $firstName, $lastName, $emailAddress, $hashedPassword, $photoFileName);

if ($insert->execute()) {
    $newUserId = $insert->insert_id;

    // 6) Save session variables
    $_SESSION['id'] = $newUserId;
    $_SESSION['userType'] = $userType;

    $insert->close();
    $conn->close();

    // 7) Redirect to user's page
    header("Location: user.html");
    exit();
} else {
    echo "Insert error: " . $insert->error;
}

$insert->close();
$conn->close();
?>
