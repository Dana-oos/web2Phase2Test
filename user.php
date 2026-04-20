<?php

session_start();


$host = "localhost";
$username = "root";
$password = "root";
$database = "nurish_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
die("Connection failed: " . $conn->connect_error);}



if (!isset($_SESSION['id']) || !isset($_SESSION['userType']) || $_SESSION['userType'] !== 'user') {
    header("Location: login.php");
    exit();
}

$userID = (int)$_SESSION['id'];


/* بيانات المستخدم */
$userQuery = "SELECT * FROM user WHERE id = $userID";
$userResult = $conn->query($userQuery);

if (!$userResult || $userResult->num_rows == 0) {
    die("User not found.");
}

$user = $userResult->fetch_assoc();

$firstName = $user['firstName'];
$lastName = $user['lastName'];
$email = $user['emailAddress'];
$photo = !empty($user['photoFileName']) ? $user['photoFileName'] : 'default.png';


/* عدد وصفات المستخدم */
$totalRecipes = 0;
$countRecipesQuery = "SELECT COUNT(*) AS total FROM recipe WHERE userID = $userID";
$countRecipesResult = $conn->query($countRecipesQuery);
if ($countRecipesResult && $countRecipesResult->num_rows > 0) {
    $totalRecipes = $countRecipesResult->fetch_assoc()['total'];
}

/* مجموع اللايكات على وصفات المستخدم */

$totalLikes = 0;
$totalLikesQuery = "
    SELECT COUNT(*) AS totalLikes
    FROM likes
    INNER JOIN recipe ON recipe.id = likes.recipeID
    WHERE recipe.userID = $userID
";
$totalLikesResult = $conn->query($totalLikesQuery);
if ($totalLikesResult && $totalLikesResult->num_rows > 0) {
    $totalLikes = $totalLikesResult->fetch_assoc()['totalLikes'];
}

/* التصنيفات */
$categories = [];
$categoriesQuery = "SELECT * FROM recipecategory ORDER BY categoryName ASC";
$categoriesResult = $conn->query($categoriesQuery);
if ($categoriesResult) {
    while ($cat = $categoriesResult->fetch_assoc()) {
        $categories[] = $cat;
    }
}

/* الفلتر */
$selectedCategory = "all";
$where = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['categoryID'])) {
    $selectedCategory = $_POST['categoryID'];

    if ($selectedCategory !== "all") {
        $selectedCategory = (int)$selectedCategory;
        $where = "WHERE recipe.categoryID = $selectedCategory";
    }
}

/* الوصفات */
$recipesQuery = "
SELECT 
    recipe.id,
    recipe.name,
    recipe.photoFileName,
    user.firstName,
    user.photoFileName AS userPhoto,
    recipecategory.categoryName,
    COUNT(likes.recipeID) AS likesCount
FROM recipe
JOIN user ON recipe.userID = user.id
JOIN recipecategory ON recipe.categoryID = recipecategory.id
LEFT JOIN likes ON recipe.id = likes.recipeID
$where
GROUP BY recipe.id, recipe.name, recipe.photoFileName, user.firstName, user.photoFileName, recipecategory.categoryName
ORDER BY recipe.id DESC
";
$recipes = $conn->query($recipesQuery);

/* المفضلة */
$favQuery = "
SELECT recipe.id, recipe.name, recipe.photoFileName
FROM favourites
JOIN recipe ON favourites.recipeID = recipe.id
WHERE favourites.userID = $userID
ORDER BY recipe.id DESC
";
$fav = $conn->query($favQuery);

function getCategoryClass($categoryName) {
    $categoryName = strtolower(trim($categoryName));

    if ($categoryName === 'gluten-free') {
        return 'gluten';
    } elseif ($categoryName === 'sugar-free') {
        return 'sugar';
    } elseif ($categoryName === 'dairy-free') {
        return 'dairy';
    }

    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<style>
:root{
--green:#8FAF5B;
--bg:#FAF8F3;
--card:#ffffff;
}

body{
margin:0;
padding:40px;
background:var(--bg);
font-family:'Poppins',sans-serif;
}

.welcome{
color:var(--green);
margin-bottom:30px;
}

/* logout */
.logout{
position:absolute;
top:45px;
right:40px;
}

.logout img{
width:26px;
}

/* links */
a{
text-decoration:none;
color:#6F8A3A;
transition:.2s;
}

a:hover{color:var(--green);}
a:visited{color:#7A9550;}
a:active{color:#4F6425;}

/* grid */
.grid{
display:grid;
grid-template-columns:repeat(2,1fr);
gap:25px;
}

/* cards */
.card{
background:var(--card);
padding:22px;
border-radius:22px;
box-shadow:0 10px 25px rgba(0,0,0,.05);
display:flex;
flex-direction:column;
gap:12px;
transition:.25s;
}

.card:hover{
transform:translateY(-4px);
box-shadow:0 15px 35px rgba(0,0,0,.08);
}

.card h3{
margin:0;
color:#6F8A3A;
}

/* info */
.info{
align-items:center;
text-align:center;
}

.profile{
width:90px;
height:90px;
object-fit:cover;
border-radius:50%;
}

.info p{
margin:4px 0;
font-size:14px;
}

/* stats */
.stats-box{
display:flex;
justify-content:space-around;
margin-top:15px;
}

.stats-box div{
background:#F6F9EF;
padding:15px 20px;
border-radius:18px;
min-width:120px;
text-align:center;
}

.stats-box span{
font-size:28px;
font-weight:bold;
color:var(--green);
}

/* table card full width */
.table-card{
grid-column:1 / span 2;
}

/* filter */
.filter{
display:flex;
gap:10px;
margin-bottom:12px;
}

select,button{
padding:10px 14px;
border-radius:14px;
border:1px solid #ddd;
font-family:'Poppins';
}

button{
background:var(--green);
color:white;
border:none;
cursor:pointer;
}

/* table */
table{
width:100%;
border-collapse:collapse;
}

th{
background:#F1F5E8;
padding:12px;
color:#6F8A3A;
border-radius:12px;
}

td{
padding:12px;
text-align:center;
border-bottom:1px solid #eee;
}

tr:hover td{
background:#FAFCF6;
}

table img{
width:55px;
height:55px;
object-fit:cover;
border-radius:12px;
}

.mini{
width:30px!important;
height:30px!important;
object-fit:cover;
border-radius:50%;
}

/* category pills */
.cat{
font-size:13px;
padding:4px 10px;
border-radius:20px;
font-weight:600;
display:inline-block;
white-space:nowrap;
}

.gluten{
background:#F1F5E8;
color:#6F8A3A;
}

.sugar{
background:#FFF1E6;
color:#C07A3E;
}

.dairy{
background:#EEF2FA;
color:#5A6FB2;
}

/* favourite */
.fav{
margin-top:30px;
}

.fav-item{
display:flex;
gap:15px;
align-items:center;
}

.fav-item img{
width:80px;
height:80px;
object-fit:cover;
border-radius:15px;
}

.fav-item div{
display:flex;
flex-direction:column;
gap:8px;
}

.remove{
background:#E8F0D9;
color:var(--green);
border:none;
padding:8px 14px;
border-radius:12px;
cursor:pointer;
}

.logout{
position:absolute;
top:45px;
right:40px;
background:#E8F0D9;
padding:10px;
border-radius:50%;
}

.logout img{
width:22px;
filter: invert(46%) sepia(22%) saturate(516%) hue-rotate(38deg) brightness(92%) contrast(90%);
}

.empty-msg{
color:#888;
padding:15px 0;
}

.fav-form{
margin:0;
}

.fav h4{
margin:0;
}
</style>
    
<meta charset="UTF-8">
<title>Nourish | User</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="stylesheet.css">

</head>

<body>

<header class="site-header">
  <div class="container nav">
    <div class="logo-area">
      <img src="logoremoved.png" alt="Nurish logo">
      <p id="VMstatement"><em>Healthy never tasted this fun...</em></p>
    </div>

    <nav class="main-nav" aria-label="Primary"></nav>

    <div class="nav-actions">
      <input id="site-search" type="search" placeholder="Search…" >
      <a class="avatar" href="#" aria-label="Profile">
        <span aria-hidden="true">👤</span>
      </a>
    </div>
  </div>
</header>

<a href="log-out.php" class="logout">
<img src="log-out.png" alt="Logout">
</a>

<h1 class="welcome">Welcome, <?php echo ($firstName)." ".($lastName); ?> 🌿</h1>

<div class="grid">

<!-- My Information -->
<div class="card info">


<img src="uploads/<?php echo ($photo); ?>" class="profile" alt="Profile Photo">
<h3>My Information</h3>
<p><strong>Name:</strong> <?php echo ($firstName)." ".($lastName); ?></p>
<p><strong>Email:</strong> <?php echo ($email); ?></p>
</div>

<!-- Stats -->
<div class="card stats">

<a href="my-recipes.php">
<h3>My Recipes</h3>

</a>
<div class="stats-box">
<div>
<span><?php echo $totalRecipes; ?></span>
<p>Total Recipes</p>
</div>

<div>
<span><?php echo $totalLikes; ?></span>
<p>Total Likes</p>
</div>
</div>
</div>

<!-- Recipes (Full Width) -->
<div class="card table-card">
<h3>All Available Recipes</h3>

<form method="POST" action="user.php" class="filter">
<select name="categoryID">
<option value="all" <?php echo ($selectedCategory === "all") ? 'selected' : ''; ?>>All Categories</option>

<?php foreach($categories as $c){ ?>
<option value="<?php echo $c['id']; ?>" <?php echo ($selectedCategory == $c['id']) ? 'selected' : ''; ?>>
<?php echo ($c['categoryName']); ?>
</option>
<?php } ?>
</select>
<button type="submit">Filter</button>
</form>

<?php if($recipes && $recipes->num_rows > 0){ ?>
<table>
<tr>
<th>Recipe</th>
<th>Photo</th>
<th>Creator</th>
<th>Likes</th>
<th>Category</th>
</tr>

<?php while($row = $recipes->fetch_assoc()){ ?>
<tr>
<td><a href="viewRecipe.php?id=<?php echo $row['id']; ?>"><?php echo ($row['name']); ?></a></td>

<td>
<a href="viewRecipe.php?id=<?php echo $row['id']; ?>">
    
<img src="uploads/<?php echo ($row['photoFileName']); ?>" alt="Recipe Photo">
</a>
</td>

<td>
<a href="#">
<?php echo ($row['firstName']); ?><br>
<img src="uploads/<?php echo ($row['userPhoto']); ?>" class="mini" alt="Creator Photo">
</a>
</td>

<td><?php echo $row['likesCount']; ?></td>
<td><span class="cat <?php echo getCategoryClass($row['categoryName']); ?>"><?php echo htmlspecialchars($row['categoryName']); ?></span></td>
</tr>
<?php } ?>

</table>
<?php } else { ?>
<p class="empty-msg">No recipes found.</p>
<?php } ?>

</div>

</div>

<!-- Favourite -->

<div class="card fav">
<h3>My Favourite Recipes ❤️</h3>

<?php if($fav && $fav->num_rows > 0){ ?>
    <?php while($f = $fav->fetch_assoc()){ ?>
    <div class="fav-item">

    <a href="viewRecipe.php?recipeID=<?php echo $f['id']; ?>">
<img src="uploads/<?php echo ($f['photoFileName']); ?>" alt="Favourite Recipe">    </a>

    <div>
    <h4><a href="viewRecipe.php?recipeID=<?php echo $f['id']; ?>"><?php echo ($f['name']); ?></a></h4>
<!-- for remove favouritre -->

<form action="removeFavourite.php" method="POST" class="fav-form">
    <input type="hidden" name="recipeID" value="<?php echo $f['id']; ?>">
    <button type="submit" class="remove">Remove</button>
</form>
    </div>

    </div>
    <?php } ?><?php } else { ?>
    <p class="empty-msg">No favourite recipes yet.</p>
<?php } ?>

</div>

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

</body>
</html>