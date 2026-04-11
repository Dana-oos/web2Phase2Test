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

$user_id = $_SESSION['id'];


// ─── load the recipe ───────────────────────────────────────────────────
if (!isset($_GET['id'])) {
    die("❌ No recipe ID provided.");
}

$recipe_id = intval($_GET['id']);

// bring the recipe- making sure it belongs to the user
$stmt = $conn->prepare("SELECT * FROM recipe WHERE id = ? AND userID = ?");
$stmt->bind_param("ii", $recipe_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ Recipe not found or you don't have permission to edit it.");
}

$recipe = $result->fetch_assoc();
$stmt->close();

// bring ingredients
$ing_result  = $conn->query("SELECT * FROM ingredients WHERE recipeID = $recipe_id ORDER BY id ASC");
$ingredients = $ing_result->fetch_all(MYSQLI_ASSOC);

// bring instructions
$ins_result  = $conn->query("SELECT * FROM instructions WHERE recipeID = $recipe_id ORDER BY stepOrder ASC");
$instructions = $ins_result->fetch_all(MYSQLI_ASSOC);

// bring categories
$cat_result = $conn->query("SELECT * FROM recipecategory");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nourish | Edit Recipe</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        body {
            margin: 0;
            background: linear-gradient(135deg, #f6f9f0 0%, #e9f0d6 35%, #d9e6b8 70%, #c6d89a 100%);
            min-height: 100vh;
        }

        .breadcrumb {
            padding: 1px 16px 5px;
            list-style: none;
        }

        .breadcrumb a {
            display: inline;
            font-size: 18px;
            color: #556B2F;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: #445625;
            text-decoration: underline;
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
    </style>
</head>
<body class="edit-page">

    <header class="site-header">
        <div class="container nav">
            <div class="logo-area">
                <img src="logoremoved.png" alt="Nourish logo">
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
        <nav class="breadcrumb">
            <a href="my-recipes.php">↩</a>
        </nav>
    </header>

    <main>
        <h1>Edit Recipe</h1>

        <!-- Form submits to separate update-recipe.php -->
        <form id="recipeForm" method="POST" action="update-recipe.php" enctype="multipart/form-data">

            <!-- Hidden recipe ID -->
            <input type="hidden" name="recipe_id" value="<?= intval($recipe['id']) ?>">

            <label>Recipe Name *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($recipe['name']) ?>" required>

            <label>Category *</label>
            <select name="category_id" required>
                <?php while ($cat = $cat_result->fetch_assoc()): ?>
                    <option value="<?= intval($cat['id']) ?>"
                        <?= $cat['id'] == $recipe['categoryID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['categoryName']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Description *</label>
            <textarea name="description" required><?= htmlspecialchars($recipe['description']) ?></textarea>

            <label>Photo (leave empty to keep current)</label>
            <?php if (!empty($recipe['photoFileName'])): ?>
                <p>Current photo: <strong><?= htmlspecialchars($recipe['photoFileName']) ?></strong></p>
            <?php endif; ?>
            <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">

            <div class="card">
                <label>Ingredients *</label>
                <div id="ingredientsList">
                    <?php foreach ($ingredients as $ing): ?>
                        <div class="dynamic-group">
                            <input type="text" name="ingredient_name[]"
                                   value="<?= htmlspecialchars($ing['IngredientName']) ?>" required>
                            <input type="text" name="ingredient_quantity[]"
                                   placeholder="Quantity"
                                   value="<?= htmlspecialchars($ing['IngredientQuantity']) ?>" required>
                            <button type="button" class="remove-btn"
                                    onclick="this.parentElement.remove()">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-btn" onclick="addIngredient()">+ Add ingredient</button>
            </div>

            <div class="card">
                <label>Instructions *</label>
                <div id="instructionsList">
                    <?php foreach ($instructions as $ins): ?>
                        <div class="dynamic-group">
                            <textarea name="instruction_step[]"
                                      placeholder="Step description" required><?= htmlspecialchars($ins['step']) ?></textarea>
                            <button type="button" class="remove-btn"
                                    onclick="this.parentElement.remove()">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-btn" onclick="addInstruction()">+ Add step</button>
            </div>

            <label>Video URL (leave empty to keep current)</label>
            <input type="url" name="video_url" value="<?= htmlspecialchars($recipe['videoFilePath'] ?? '') ?>">

            <div class="actions">
                <button type="submit" class="btn btn-primary">Update Recipe</button>
            </div>

        </form>
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-left">
                <h3>Contact Us</h3>
                <p>📞 +966 565212266</p>
                <p>✉️ info@Nourish.com</p>
            </div>
            <div class="footer-center">
                <p>© 2026 Nourish All Rights Reserved.</p>
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
