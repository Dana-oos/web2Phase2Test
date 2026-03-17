

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: signup.html");
    exit();
}

$conn = new mysqli("localhost", "root", "root", "nurish db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$firstName = trim($_POST['firstName']);
$lastName = trim($_POST['lastName']);
$emailAddress = trim($_POST['emailAddress']);
$password = $_POST['password'];
$userType = "user";

// Check existing user
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

// Check blocked user
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

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);


$tempPhotoName = "default.png";

$insert = $conn->prepare("INSERT INTO user (userType, firstName, lastName, emailAddress, password, photoFileName) VALUES (?, ?, ?, ?, ?, ?)");
if (!$insert) {
    die("Prepare failed for insert: " . $conn->error);
}
$insert->bind_param("ssssss", $userType, $firstName, $lastName, $emailAddress, $hashedPassword, $tempPhotoName);

if ($insert->execute()) {
    $newUserId = $insert->insert_id;

    $photoFileName = "default.png";
   
    if (isset($_FILES['photoFileName']) && $_FILES['photoFileName']['error'] === 0 && !empty($_FILES['photoFileName']['name'])) {
        $uploadDir = "uploads/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = $_FILES['photoFileName']['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

       
        $photoFileName = "user_" . $newUserId . "." . $extension;

        $targetFile = $uploadDir . $photoFileName;

        if (move_uploaded_file($_FILES['photoFileName']['tmp_name'], $targetFile)) {
          
            $updatePhoto = $conn->prepare("UPDATE user SET photoFileName = ? WHERE id = ?");
            if (!$updatePhoto) {
                die("Prepare failed for update: " . $conn->error);
            }
            $updatePhoto->bind_param("si", $photoFileName, $newUserId);
            $updatePhoto->execute();
            $updatePhoto->close();
        }
    }

    $_SESSION['id'] = $newUserId;
    $_SESSION['userType'] = $userType;

    $insert->close();
    $conn->close();

   // later change it to user.php
    header("Location: user.html");
    exit();
} else {
    echo "Insert error: " . $insert->error;
}

$insert->close();
$conn->close();
//testtest
?>
