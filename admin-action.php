<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Must be admin
if (!isset($_SESSION['id']) || $_SESSION['userType'] !== 'admin') {
    echo "false";
    exit();
}

// Must be POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "false";
    exit();
}

$host     = "localhost";
$user     = "root";
$password = "root";
$database = "nurish db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo "false";
    exit();
}

$report_id     = intval($_POST['report_id']);
$recipe_id     = intval($_POST['recipe_id']);
$creator_id    = intval($_POST['creator_id']);
$chosen_action = $_POST['chosen_action'] ?? '';

if ($chosen_action === 'block') {

    // Get creator info to add to blocked table
    $stmt = $conn->prepare("SELECT firstName, lastName, emailAddress FROM user WHERE id = ?");
    $stmt->bind_param("i", $creator_id);
    $stmt->execute();
    $creator = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($creator) {

        // Get all recipes by this user
        $recipes_result = $conn->query(
            "SELECT id, photoFileName FROM recipe WHERE userID = $creator_id"
        );

        while ($recipe = $recipes_result->fetch_assoc()) {
            $rid = intval($recipe['id']);

            // Delete recipe photo file
            if (!empty($recipe['photoFileName'])) {
                $photoPath = "uploads/" . $recipe['photoFileName'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }

            // Delete all associated data for this recipe
            $conn->query("DELETE FROM likes        WHERE recipeID = $rid");
            $conn->query("DELETE FROM favourites   WHERE recipeID = $rid");
            $conn->query("DELETE FROM comment      WHERE recipeID = $rid");
            $conn->query("DELETE FROM report       WHERE recipeID = $rid");
            $conn->query("DELETE FROM ingredients  WHERE recipeID = $rid");
            $conn->query("DELETE FROM instructions WHERE recipeID = $rid");
            $conn->query("DELETE FROM recipe       WHERE id       = $rid");
        }

        // Add user to blocked users table
        $stmt = $conn->prepare(
            "INSERT INTO blockeduser (firstName, lastName, emailAddress)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param(
            "sss",
            $creator['firstName'],
            $creator['lastName'],
            $creator['emailAddress']
        );
        $stmt->execute();
        $stmt->close();

        // Delete the user from users table
        $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
        $stmt->bind_param("i", $creator_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Delete the report regardless of action (block or dismiss)
$stmt = $conn->prepare("DELETE FROM report WHERE id = ?");
$stmt->bind_param("i", $report_id);

if ($stmt->execute()) {
    $result = ['success' => true, 'action' => $chosen_action];
    
    // If blocking, include user info to update the table
    if ($chosen_action === 'block' && isset($creator)) {
        $result['user'] = [
            'firstName' => $creator['firstName'],
            'lastName'  => $creator['lastName'],
            'email'     => $creator['emailAddress']
        ];
    }
    echo json_encode($result);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
exit();
?>
