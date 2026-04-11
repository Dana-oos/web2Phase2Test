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


// ─── bring all recipes that belongs to this user
$stmt = $conn->prepare("
    SELECT r.*,
           (SELECT COUNT(*) FROM likes WHERE recipeID = r.id) AS likeCount
    FROM recipe r
    WHERE r.userID = ?
    ORDER BY r.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recipes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Nourish | My Recipes</title>
  <link rel="stylesheet" href="stylesheet.css">
  <style>
    :root {
      --primary: #8FAE3B;
      --secondary: #D9C7A0;
      --dark: #2f3a1f;
      --light: #f9faf7;
      --border: #dfe5d4;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      background: linear-gradient(135deg, #f6f9f0 0%, #e9f0d6 35%, #d9e6b8 70%, #c6d89a 100%);
      min-height: 100vh;
      color: var(--dark);
    }

    .brand {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .brand h1 {
      margin: 0;
      color: var(--primary);
      font-size: 1.4rem;
    }

    .add-link {
      background: var(--primary);
      color: #fff;
      padding: 0.6rem 1.4rem;
      border-radius: 25px;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .add-link:hover { opacity: 0.9; }

    main {
      max-width: 1100px;
      margin: 2rem auto;
      padding: 2rem;
      background: #f6f9f0;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.05);
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
    }

    thead { background: #dbe6c6; }

    th, td {
      padding: 0.9rem;
      border-bottom: 1px solid var(--border);
      vertical-align: top;
      text-align: left;
    }

    th { font-weight: 700; }

    .recipe-card { text-align: center; }

    .recipe-card img {
      width: 150px;
      height: 140px;
      object-fit: cover;
      border-radius: 10px;
      display: block;
      margin: 0 auto 8px;
      border: 1px solid var(--border);
    }

    .recipe-link {
      text-decoration: none;
      color: var(--dark);
      font-weight: 600;
    }

    ul {
      margin: 0;
      padding-left: 1.1rem;
    }

    ul li { margin-bottom: 0.3rem; }

    .video-link {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }

    .no-video {
      color: #999;
      font-style: italic;
    }

    .likes {
      font-weight: 700;
      color: var(--primary);
      font-size: 1.1rem;
    }

    .action-link {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 0.4rem;
    }

    .action-link.delete { color: #c94a4a; }
    .action-link:hover { text-decoration: underline; }

    .no-recipes {
      text-align: center;
      padding: 3rem;
      color: #666;
      font-size: 1.1rem;
    }

    ul.breadcrumb {
      padding: 1px 16px 5px;
      list-style: none;
    }

    ul.breadcrumb li { display: inline; font-size: 18px; }

    ul.breadcrumb li+li:before {
      padding: 8px;
      color: #556B2F;
      content: "/\00a0";
    }

    ul.breadcrumb li a {
      color: #556B2F;
      text-decoration: none;
    }

    ul.breadcrumb li a:hover {
      color: #445625;
      text-decoration: underline;
    }
  </style>
</head>
<body>

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
    <nav>
      <ul class="breadcrumb">
        <li><a href="user.php">↩</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <div class="brand">
      <h1>My Recipes</h1>
      <a href="add-recipe.php" class="add-link">+ Add New Recipe</a>
    </div>

      
      <!-- if user has no recipes  -->
    <?php if (empty($recipes)): ?>

      <div class="no-recipes">
        <p>You haven't added any recipes yet.</p>
        <a href="add-recipe.php" class="add-link">+ Add Your First Recipe</a>
      </div>

    <?php else: ?>

      <table>
        <thead>
          <tr>
            <th>Recipe</th>
            <th>Ingredients</th>
            <th>Instructions</th>
            <th>Video</th>
            <th>Likes</th>
            <th>Edit</th>
            <th>Delete</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recipes as $recipe): ?>
            <?php
              $ing = $conn->query("SELECT * FROM ingredients WHERE recipeID = " . intval($recipe['id']));
              $ingredients = $ing->fetch_all(MYSQLI_ASSOC);

              $ins = $conn->query("SELECT * FROM instructions WHERE recipeID = " . intval($recipe['id']) . " ORDER BY stepOrder ASC");
              $instructions = $ins->fetch_all(MYSQLI_ASSOC);
            ?>
            <tr>
              <!-- Recipe name and photo -->
              <td>
                <div class="recipe-card">
                  <a href="view-recipe.php?id=<?= intval($recipe['id']) ?>" class="recipe-link">
                    <img src="uploads/<?= htmlspecialchars($recipe['photoFileName']) ?>"
                         alt="<?= htmlspecialchars($recipe['name']) ?>">
                    <?= htmlspecialchars($recipe['name']) ?>
                  </a>
                </div>
              </td>

              <!-- Ingredients -->
              <td>
                <ul>
                  <?php if (!empty($ingredients)): ?>
                    <?php foreach ($ingredients as $ing_row): ?>
                      <li>
                        <?= htmlspecialchars($ing_row['IngredientQuantity']) ?>
                        <?= htmlspecialchars($ing_row['IngredientName']) ?>
 </li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li><em>No ingredients</em></li>
                  <?php endif; ?>
                </ul>
              </td>

              <!-- Instructions -->
              <td>
                <ul>
                  <?php if (!empty($instructions)): ?>
                    <?php foreach ($instructions as $ins_row): ?>
                      <li><?= htmlspecialchars($ins_row['step']) ?></li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li><em>No instructions</em></li>
                  <?php endif; ?>
                </ul>
              </td>

              <!-- Video -->
              <td>
                <?php if (!empty($recipe['videoFilePath'])): ?>
                  <a href="<?= htmlspecialchars($recipe['videoFilePath']) ?>"
                     target="_blank" class="video-link">Watch video</a>
                <?php else: ?>
                  <span class="no-video">No video</span>
                <?php endif; ?>
              </td>

              <!-- Likes -->
              <td class="likes"><?= intval($recipe['likeCount']) ?></td>

              <!-- Edit -->
              <td>
                <a href="edit-recipe.php?id=<?= intval($recipe['id']) ?>"
                   class="action-link">Edit</a>
              </td>

              <!-- Delete, separate delete-recipe.php -->
              <td>
                <a href="delete-recipe.php?id=<?= intval($recipe['id']) ?>"
                   class="action-link delete"
                   onclick="return confirm('Are you sure you want to delete this recipe? This cannot be undone.')">
                  Delete
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php endif; ?>
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

</body>
</html>
