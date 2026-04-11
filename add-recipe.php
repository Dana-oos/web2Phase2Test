<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
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

$user_id      = $_SESSION['id'];
$errorMessage = "";


?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nourish | Add Recipe</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="stylesheet.css">

    <style>
        .add-recipe-page {
            position: relative;
            min-height: 100vh;
            width: 100%;
        }

        .add-recipe-page main, header, footer {
            position: relative;
            z-index: 2;
        }

        body {
            margin: 0;
            background: linear-gradient(135deg, #f6f9f0 0%, #e9f0d6 35%, #d9e6b8 70%, #c6d89a 100%);
            min-height: 100vh;
            font-family: classic serif;
        }

        .card {
            background: var(--card);
            padding: 22px;
            border-radius: 22px;
            box-shadow: 0 10px 25px rgba(0,0,0,.05);
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: .25s;
            margin-top: 10px;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0,0,0,.08);
        }

        .error-msg {
            background: #ffe0e0;
            color: #c00;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body class="add-recipe-page">

    <header class="site-header">
        <div class="container nav">
            <div class="logo-area">
                <img src="logoremoved.png" alt="Nurish logo">
                <p id="VMstatement"><em>Healthy never tasted this fun...</em></p>
            </div>
            <nav class="main-nav" aria-label="Primary"></nav>
            <div class="nav-actions">
                <input id="site-search" type="search" placeholder="Search…">
                <a class="avatar" href="#" aria-label="Profile">
                    <span aria-hidden="true">👤</span>
                </a>
            </div>
        </div>
    </header>

    <main>

        <h1>Add Recipe</h1>

        <?php if (!empty($errorMessage)): ?>
            <div class="error-msg"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <!-- Form submits to separate save-recipe.php -->
        <form id="recipeForm" method="POST" action="save-recipe.php" enctype="multipart/form-data">

            <label>Recipe Name *</label>
            <input type="text" name="name" required>

            <label>Category *</label>
            <select name="category_id" required>
                <option value="">Select</option>
                <?php
                $result = $conn->query("SELECT * FROM recipecategory");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . intval($row['id']) . "'>" . htmlspecialchars($row['categoryName']) . "</option>";
                }
                ?>
            </select>

            <label>Description *</label>
            <textarea name="description" required></textarea>

            <label>Photo *</label>
            <input type="file" name="photo" accept="image/*" required>

            <div class="card">
                <label>Ingredients *</label>
                <div id="ingredientsList"></div>
                <button type="button" class="add-btn" onclick="addIngredient()">+ Add ingredient</button>
            </div>

            <div class="card">
                <label>Instructions *</label>
                <div id="instructionsList"></div>
                <button type="button" class="add-btn" onclick="addInstruction()">+ Add step</button>
            </div>

            <label>Video URL (optional)</label>
            <input type="url" name="video_url">

            <div class="actions">
                <button type="submit" class="btn btn-primary">Save Recipe</button>
            </div>

        </form>

    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-left">
                <h3>Contact Us</h3>
                <p>📞 +966 565212266</p>
                <p>✉️ info@Nurish.com</p>
            </div>
            <div class="footer-center">
                <p>© 2026 Nurish All Rights Reserved.</p>
            </div>
            <div class="footer-right">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><img src="instagram.png" alt="Instagram"></a>
                    <a href="#"><img src="X.png" alt="X"></a>
                    <a href="#"><img src="facebook.png" alt="Facebook"></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
