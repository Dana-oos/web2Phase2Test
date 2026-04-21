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

//  Check recipe ID 
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid recipe ID.");
}
$recipeID = intval($_GET['id']);

// get recipe and creator info 
$stmt = $conn->prepare("
    SELECT r.*, rc.categoryName, u.firstName, u.lastName, u.photoFileName AS creatorPhoto
    FROM recipe r
    JOIN recipecategory rc ON r.categoryID = rc.id
    JOIN user u ON r.userID = u.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $recipeID);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$recipe) {
    die("Recipe not found.");
}

// Retrieve ingredients
$ing = $conn->prepare("SELECT * FROM ingredients WHERE recipeID = ? ORDER BY id");
$ing->bind_param("i", $recipeID);
$ing->execute();
$ingredients = $ing->get_result()->fetch_all(MYSQLI_ASSOC);
$ing->close();

// Retrieve instructions
$ins = $conn->prepare("SELECT * FROM instructions WHERE recipeID = ? ORDER BY stepOrder");
$ins->bind_param("i", $recipeID);
$ins->execute();
$instructions = $ins->get_result()->fetch_all(MYSQLI_ASSOC);
$ins->close();

// Retrieve comments with user names
$com = $conn->prepare("
    SELECT c.*, u.firstName, u.lastName
    FROM comment c
    JOIN user u ON c.userID = u.id
    WHERE c.recipeID = ?
    ORDER BY c.date DESC
");
$com->bind_param("i", $recipeID);
$com->execute();
$comments = $com->get_result()->fetch_all(MYSQLI_ASSOC);
$com->close();

$currentUserID = intval($_SESSION['id']);
$userType      = $_SESSION['userType'] ?? '';
$isCreator     = ($currentUserID === intval($recipe['userID']));
$isAdmin       = ($userType === 'admin');
$showButtons   = !$isCreator && !$isAdmin;





$alreadyFavourited = false;
$alreadyLiked      = false;
$alreadyReported   = false;

if ($showButtons) {
    $s = $conn->prepare("SELECT 1 FROM favourites WHERE userID = ? AND recipeID = ?");
    $s->bind_param("ii", $currentUserID, $recipeID);
    $s->execute();
    $alreadyFavourited = (bool)$s->get_result()->fetch_assoc();
    $s->close();

    $s = $conn->prepare("SELECT 1 FROM likes WHERE uesrID = ? AND recipeID = ?");
    $s->bind_param("ii", $currentUserID, $recipeID);
    $s->execute();
    $alreadyLiked = (bool)$s->get_result()->fetch_assoc();
    $s->close();

    $s = $conn->prepare("SELECT 1 FROM report WHERE userID = ? AND recipeID = ?");
    $s->bind_param("ii", $currentUserID, $recipeID);
    $s->execute();
    $alreadyReported = (bool)$s->get_result()->fetch_assoc();
    $s->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recipe – <?= htmlspecialchars($recipe['name']) ?></title>
  <link rel="stylesheet" href="stylesheet.css" />

  <style>
    .title-with-lines {
      display: flex; align-items: center; justify-content: center;
      gap: 16px; font-weight: 600; color: #228B22; margin: 30px 0 14px;
    }
    .title-with-lines::before, .title-with-lines::after {
      content: ""; flex: 1; border-bottom: 1px solid #228B22; height: 0;
    }

    .feedbackIcons {
      display: flex; justify-content: center; align-items: center;
      gap: 18px; margin: 10px 0 16px;
    }
    .feedbackIcons img { width: 42px; height: 42px; cursor: pointer; transition: 0.25s ease; }
    .feedbackIcons img:hover { transform: scale(1.12); }
    .feedbackIcons img.disabled { opacity: 0.35; cursor: not-allowed; pointer-events: none; }
    .feedbackIcons a { display: inline-block; }

    .recipe-media { display: flex; justify-content: center; margin: 10px 0 26px; }
    #recipeimg {
      width: 360px; height: auto; border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.12); transition: 0.25s ease;
    }
    #recipeimg:hover { transform: scale(1.03); }

    .recipe-page {
      max-width: 1100px; margin: 0 auto 40px; padding: 28px;
      background: rgba(255,255,255,0.75); border-radius: 22px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08); backdrop-filter: blur(6px);
    }

    .section-card {
      background: rgba(255,255,255,0.88); border: 1px solid rgba(0,0,0,0.06);
      border-radius: 18px; padding: 18px 20px;
      box-shadow: 0 6px 16px rgba(0,0,0,0.05); margin-bottom: 26px;
    }
    .section-title {
      display: flex; align-items: center; gap: 10px;
      margin: 12px 0 14px; color: #2e7d32;
    }
    .section-title::before {
      content: ""; width: 10px; height: 10px; border-radius: 50%;
      background: #2e7d32; flex: 0 0 10px;
    }

    #RecipeDetails, #recipeIng, #recipeInst {
      font-family: "Georgia", serif; font-size: 18px; line-height: 1.7;
    }
    #recipeIng { padding-left: 22px; margin: 0; }
    #recipeIng li { margin: 8px 0; }
    #recipeInst { padding-left: 0; margin: 0; list-style-position: inside; }
    #recipeInst li {
      margin: 14px 0; padding: 14px; border-radius: 14px;
      background: rgba(46,125,50,0.06); border: 1px solid rgba(46,125,50,0.15);
      line-height: 1.7;
    }

    .video-link {
      color: #8FAE3B;
      text-decoration: none;
      font-weight: 600;
    }

    .no-video {
      color: #999;
      font-style: italic;
    }

    #recipeCreator {
      height: 400px; width: 300px; display: block;
      margin: 10px auto 0; border-radius: 16px; transition: 0.25s ease;
    }
    #recipeCreator:hover { transform: scale(1.03); }
    #creatore-name {
      text-align: center; font-size: 200%;
      font-style: sans-serif; margin: 12px 0 6px;
    }

    .reviews {
      text-align: center; padding: 60px 15px; background: #fff;
      border-radius: 20px; width: min(1100px, 95%);
      margin: 50px auto; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .reviews h2 { font-size: 2rem; color: #1e1e1e; margin-bottom: 6px; }
    .reviews p { color: #666; margin-bottom: 26px; font-size: 1rem; }

    .comment-panel {
      max-width: 900px; margin: 0 auto 24px;
      display: flex; flex-direction: column; gap: 12px; text-align: left;
    }
    .comment-panel textarea {
      width: 100%; padding: 12px 14px; border-radius: 12px;
      border: 1px solid rgba(0,0,0,0.15); outline: none;
      font-size: 0.95rem; background: rgba(255,255,255,0.95);
    }
    .comment-btn {
      width: fit-content; padding: 10px 16px; border: none;
      border-radius: 12px; background: #2e7d32; color: white;
      cursor: pointer; font-weight: 600; transition: 0.2s ease;
    }
    .comment-btn:hover { transform: translateY(-2px); }

    .reviews-grid {
      display: flex; flex-direction: column; gap: 16px;
      max-width: 900px; margin: 0 auto;
    }
    .review-card {
      background: #fff; border-radius: 14px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      padding: 20px 28px; width: 100%; transition: 0.25s ease; text-align: left;
    }
    .review-text { font-size: 0.95rem; color: #333; line-height: 1.5; margin-bottom: 12px; }
    .review-footer {
      display: flex; align-items: center; justify-content: space-between;
      border-top: 1px solid #eee; padding-top: 8px;
    }
    .client-name { font-weight: 700; font-size: 0.95rem; color: #000; }
    .review-date { font-size: 0.85rem; color: #666; font-weight: 500; white-space: nowrap; }

    .breadcrumb { padding: 1px 16px 5px; list-style: none; }
    .breadcrumb a { display: inline; font-size: 18px; color: #556B2F; text-decoration: none; }
    .breadcrumb a:hover { color: #445625; text-decoration: underline; }
  </style>
</head>
<body>

<header class="site-header">
  <div class="container nav">
    <div class="logo-area">
      <img src="logoremoved.png" alt="Nourish logo">
      <p id="VMstatement"><em>Healthy never tasted this fun..</em></p>
    </div>
    <nav class="main-nav" aria-label="Primary"></nav>
    <div class="nav-actions">
      <input id="site-search" type="search" placeholder="Search…">
      <a class="avatar" href="#" aria-label="Profile"><span aria-hidden="true">👤</span></a>
    </div>
  </div>
  
</header>

<h1 class="title-with-lines"><em><?= htmlspecialchars($recipe['name']) ?></em></h1>

<!--Feedback buttons  -->
<?php if ($showButtons): ?>
<div class="feedbackIcons">

  <!-- LIKE -->
  <?php if ($alreadyLiked): ?>
    <img src="like2.png" alt="like recipe icon" class="disabled" title="Already liked">
  <?php else: ?>
    <a href="add-like.php?recipeID=<?= $recipeID ?>">
      <img src="like2.png" alt="like recipe icon" title="Like this recipe">
    </a>
  <?php endif; ?>

  <!-- FAVOURITE -->
  <?php if ($alreadyFavourited): ?>
    <img src="favorite2.png" alt="add to favourite icon" class="disabled" title="Already in favourites">
  <?php else: ?>
    <a href="add-favourite.php?recipeID=<?= $recipeID ?>">
      <img src="favorite2.png" alt="add to favourite icon" title="Add to favourites">
    </a>
  <?php endif; ?>

  <!-- REPORT -->
  <?php if ($alreadyReported): ?>
    <img src="report2.png" alt="report a recipe" class="disabled" title="Already reported">
  <?php else: ?>
    <a href="add-report.php?recipeID=<?= $recipeID ?>">
      <img src="report2.png" alt="report a recipe" title="Report this recipe">
    </a>
  <?php endif; ?>

</div>
<?php endif; ?>

<!-- Recipe image -->
<div class="recipe-media">
  <img src="uploads/<?= htmlspecialchars($recipe['photoFileName']) ?>" alt="Recipe photo" id="recipeimg">
</div>

<div class="recipe-page">

  <!-- Details -->
  <div class="section-card">
    <h2 class="section-title"><em>Recipe's Details</em></h2>
    <p id="RecipeDetails">
      Category: <?= htmlspecialchars($recipe['categoryName']) ?><br><br>
      Description: <?= nl2br(htmlspecialchars($recipe['description'])) ?>
    </p>
  </div>

  <!-- Ingredients -->
  <div class="section-card">
    <h2 class="section-title"><em>Recipe's ingredients</em></h2>
    <ul id="recipeIng">
      <?php foreach ($ingredients as $ing_row): ?>
        <li><?= htmlspecialchars($ing_row['IngredientQuantity']) ?> <?= htmlspecialchars($ing_row['IngredientName']) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Instructions -->
  <div class="section-card">
    <h2 class="section-title"><em>Recipe's instructions</em></h2>
    <ol id="recipeInst">
      <?php foreach ($instructions as $ins_row): ?>
        <li><?= nl2br(htmlspecialchars($ins_row['step'])) ?></li>
      <?php endforeach; ?>
    </ol>
  </div>

  <!-- Video -->
  <?php if (!empty($recipe['videoFilePath'])): ?>
  <div class="section-card">
    <h2 class="section-title"><em>Recipe's video</em></h2>
    <?php if (!empty($recipe['videoFilePath'])): ?>
                  <a href="<?= htmlspecialchars($recipe['videoFilePath']) ?>"
                     target="_blank" class="video-link">Watch video</a>
                <?php else: ?>
                  <span class="no-video">No video</span>
                <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Creator -->
  <div class="section-card">
    <h2 class="section-title"><em>Recipe's creator</em></h2>
    <img src="uploads/<?= htmlspecialchars($recipe['creatorPhoto']) ?>"
         alt="Recipe's creator picture" id="recipeCreator">
    <p id="creatore-name"><?= htmlspecialchars($recipe['firstName']) ?></p>
  </div>

</div><!-- /recipe-page -->

<!--  Comments section -->
<section class="reviews" id="comments">
  <h2>Comments</h2>
  <p>We would love to hear about your experience!</p>

  <form class="comment-panel" action="add-comment.php" method="POST">
    <input type="hidden" name="recipeID" value="<?= $recipeID ?>">
    <textarea name="comment" id="commentText" rows="4"
              placeholder="Write your comment..." required></textarea>
    <button type="submit" class="comment-btn">Post Comment</button>
  </form>

  <div class="reviews-grid" id="commentsBox">
    <?php foreach ($comments as $c): ?>
    <div class="review-card">
      <p class="review-text">"<?= nl2br(htmlspecialchars($c['comment'])) ?>"</p>
      <div class="review-footer">
        <span class="client-name">
          <?= htmlspecialchars($c['firstName'] . ' ' . $c['lastName']) ?>
        </span>
        <span class="review-date">
          <?= date('j-n-Y', strtotime($c['date'])) ?>
        </span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

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
