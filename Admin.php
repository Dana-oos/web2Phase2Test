<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// a. Check that the user is an admin
if (!isset($_SESSION['id']) || $_SESSION['userType'] !== 'admin') {
    header("Location: login.php?error=You+must+be+an+admin+to+access+this+page.");
    exit();
}

$host     = "localhost";
$user     = "root";
$password = "root";
$database = "nurish db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin_id = $_SESSION['id'];

// b. Retrieve admin information
$stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// c. Retrieve all recipe reports with recipe and creator info
$reports = $conn->query("
    SELECT rp.id AS reportID,
           r.id  AS recipeID,
           r.name AS recipeName,
           u.id   AS creatorID,
           u.firstName AS creatorFirstName,
           u.lastName  AS creatorLastName,
           u.emailAddress AS creatorEmail,
           u.photoFileName AS creatorPhoto
    FROM report rp
    JOIN recipe r ON rp.recipeID = r.id
    JOIN user u   ON r.userID    = u.id
    ORDER BY rp.id DESC
")->fetch_all(MYSQLI_ASSOC);

// d. Retrieve all blocked users
$blocked = $conn->query("
    SELECT * FROM blockeduser ORDER BY id DESC
")->fetch_all(MYSQLI_ASSOC);

// Counts
$pendingCount = count($reports);
$blockedCount = count($blocked);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin</title>
  <link rel="stylesheet" href="stylesheet.css">
  <style>
    .admin-wrap {
      width: min(1100px, 95%);
      margin: 40px auto 60px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      padding: 26px 24px;
    }

    .admin-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-bottom: 18px;
      padding-bottom: 14px;
      border-bottom: 1px solid #eee;
    }

    .admin-title { margin: 0; font-size: 26px; font-weight: 700; }
    .admin-sub   { margin: 6px 0 0; color: #666; font-size: 14px; }

    .logout-link {
      text-decoration: none;
      padding: 10px 16px;
      border-radius: 999px;
      background: #4f7f2f;
      color: #fff;
      font-weight: 700;
      display: inline-block;
    }
    .logout-link:hover { opacity: 0.9; }

    .grid2 {
      display: grid;
      grid-template-columns: 1.1fr .9fr;
      gap: 18px;
      margin-top: 10px;
    }

    .card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
      padding: 18px;
      border: 1px solid #f1f1f1;
    }

    .cardTitle { margin: 0 0 12px; font-size: 18px; font-weight: 700; color: #355f1e; }

    .line {
      background: #fafafa;
      border: 1px solid #eee;
      border-radius: 14px;
      padding: 12px 14px;
      margin-bottom: 10px;
      font-size: 14px;
    }

    .label { font-weight: 700; color: #4f7f2f; margin-right: 6px; }

    .stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 6px; }

    .stat {
      text-align: center;
      padding: 16px 12px;
      border-radius: 16px;
      background: #fafafa;
      border: 1px solid #eee;
    }

    .statNum   { font-size: 30px; font-weight: 800; color: #228B22; line-height: 1; }
    .statLabel { margin-top: 8px; color: #666; font-size: 13px; }

    .sectionTitle { margin: 26px 0 12px; font-size: 18px; font-weight: 700; color: #355f1e; }

    .tableCard {
      border-radius: 16px;
      overflow: auto;
      border: 1px solid #eee;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }

    .card:hover, .tableCard:hover, .admin-wrap:hover {
      transform: translateY(-4px);
      box-shadow: 0 15px 35px rgba(0,0,0,.08);
    }

    table { width: 100%; border-collapse: collapse; min-width: 820px; background: #fff; }

    thead th {
      padding: 14px 16px;
      font-size: 14px;
      font-weight: 700;
      background: #f6f6f6;
      color: #355f1e;
      border-bottom: 1px solid #eee;
      text-align: left;
    }

    tbody td {
      padding: 16px;
      border-top: 1px solid #f0f0f0;
      vertical-align: middle;
      font-size: 14px;
      color: #6b6a58;
    }

    .recipeLink { color: #4f7f2f; font-weight: 700; text-decoration: none; }
    .recipeLink:hover { text-decoration: underline; }

    .creator { display: flex; align-items: center; gap: 12px; }

    .avatar {
      width: 70px; height: 70px;
      border-radius: 16px;
      overflow: hidden;
      border: 1px solid #eee;
      background: #f6f6f6;
      line-height: 70px;
      text-align: center;
      font-weight: 800;
      color: #555;
      flex: 0 0 auto;
    }

    .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .creatorName  { margin: 0; font-weight: 700; color: #355f1e; }
    .creatorEmail { margin: 4px 0 0; font-size: 14px; color: #8a8976; }

    .pill {
      display: inline-block;
      padding: 10px 12px;
      border-radius: 999px;
      background: #fff;
      border: 1px solid #eee;
      cursor: pointer;
      font-size: 14px;
      margin-right: 8px;
      margin-bottom: 8px;
    }

    .btn {
      border: 0;
      cursor: pointer;
      background: #4f7f2f;
      color: #fff;
      font-weight: 700;
      padding: 10px 16px;
      border-radius: 999px;
    }

    .blocked-head { font-size: 14px; font-weight: 700; color: #355f1e; }
    #blockedTbody td { font-style: italic; }

    .no-data { padding: 20px; color: #999; font-style: italic; text-align: center; }

    input[type="radio"] { accent-color: #2f6b2f; }

    @media (max-width: 950px) {
      .grid2 { grid-template-columns: 1fr; }
      table  { min-width: 780px; }
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
        <a class="logout-link" href="log-out.php">Sign out</a>
      </div>
    </div>
  </header>

  <main class="admin-wrap">

    <div class="admin-head">
      <div>
        <h1 class="admin-title">
          Welcome, <?= htmlspecialchars($admin['firstName'])." ".$admin['lastName']; ?>
        </h1>
        <p class="admin-sub">Admin Dashboard</p>
      </div>
    </div>

    <section class="grid2">
      <div class="card">
        <h2 class="cardTitle">My Information</h2>
        <div class="line">
          <span class="label">Name:</span>
          <?= htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']) ?>
        </div>
        <div class="line">
          <span class="label">Email:</span>
          <?= htmlspecialchars($admin['emailAddress']) ?>
        </div>
      </div>

      <div class="card">
        <h2 class="cardTitle">Overview</h2>
        <div class="stats">
          <div class="stat">
            <div class="statNum"><?= $pendingCount ?></div>
            <div class="statLabel">Pending Reports</div>
          </div>
          <div class="stat">
            <div class="statNum"><?= $blockedCount ?></div>
            <div class="statLabel">Blocked Users</div>
          </div>
        </div>
      </div>
    </section>

    <h2 class="sectionTitle">Reported Recipes</h2>
    <div class="tableCard">
      <table>
        <thead>
          <tr>
            <th>Recipe Name</th>
            <th>Recipe Creator</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="reportsTableBody">
          <?php if (empty($reports)): ?>
            <tr class="no-data-row">
              <td colspan="3" class="no-data">No reported recipes.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($reports as $r): ?>
            <tr id="report-row-<?= $r['reportID'] ?>">
              <td>
                <a href="viewRecipe.php?id=<?= intval($r['recipeID']) ?>"
                   class="recipeLink">
                  <?= htmlspecialchars($r['recipeName']) ?>
                </a>
              </td>

              <td>
                <div class="creator">
                  <div class="avatar">
                    <?php if (!empty($r['creatorPhoto'])): ?>
                      <img src="uploads/<?= htmlspecialchars($r['creatorPhoto']) ?>"
                           alt="<?= htmlspecialchars($r['creatorFirstName']) ?>">
                    <?php else: ?>
                      <?= strtoupper(substr($r['creatorFirstName'], 0, 1) . substr($r['creatorLastName'], 0, 1)) ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <p class="creatorName">
                      <?= htmlspecialchars($r['creatorFirstName'] . ' ' . $r['creatorLastName']) ?>
                    </p>
                    <p class="creatorEmail">
                      <?= htmlspecialchars($r['creatorEmail']) ?>
                    </p>
                  </div>
                </div>
              </td>

              <td>
                <form class="admin-action-form" data-report-id="<?= $r['reportID'] ?>">
                  <input type="hidden" name="report_id"  value="<?= intval($r['reportID']) ?>">
                  <input type="hidden" name="recipe_id"  value="<?= intval($r['recipeID']) ?>">
                  <input type="hidden" name="creator_id" value="<?= intval($r['creatorID']) ?>">

                  <label class="pill">
                    <input type="radio" name="chosen_action" value="block" checked> Block User
                  </label>
                  <label class="pill">
                    <input type="radio" name="chosen_action" value="dismiss"> Dismiss Report
                  </label>

                  <div style="height:4px"></div>
                  <button type="submit" class="btn">
                    Submit
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h2 class="sectionTitle">Blocked Users List</h2>
    <div class="tableCard">
      <table>
        <thead>
          <tr>
            <th class="blocked-head">Name</th>
            <th class="blocked-head">Email Address</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($blocked)): ?>
            <tr>
              <td colspan="2" class="no-data">No blocked users.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($blocked as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['firstName'] . ' ' . $b['lastName']) ?></td>
              <td><?= htmlspecialchars($b['emailAddress']) ?></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

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

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
  $(document).ready(function () {
    $('.admin-action-form').on('submit', function (e) {
      e.preventDefault();

      const $form    = $(this);
      const reportId = $form.data('report-id');

      $.ajax({
  url:    'admin-action.php',
  method: 'POST',
  data:   $form.serialize(),
  dataType: 'json',
  success: function (response) {
    if (response.success) {
      $('#report-row-' + reportId).remove();

      // Update pending reports count
      const $counter = $('.statNum').first();
      $counter.text(parseInt($counter.text()) - 1);

      // If block action, add user to blocked table instantly
      if (response.action === 'block' && response.user) {
        const u = response.user;
        const fullName = u.firstName + ' ' + u.lastName;

        // Remove the "no blocked users" row if it exists
        $('#blockedTbody .no-data').closest('tr').remove();

        $('table').last().find('tbody').prepend(
          '<tr><td>' + fullName + '</td><td>' + u.email + '</td></tr>'
        );

        // Update blocked users count
        const $blockedCounter = $('.statNum').last();
        $blockedCounter.text(parseInt($blockedCounter.text()) + 1);
      }

      if ($('#reportsTableBody').find('tr').length === 0) {
        $('#reportsTableBody').html('<tr><td colspan="3" class="no-data">No reported recipes.</td></tr>');
      }
    } else {
      alert('Operation failed. Please try again.');
    }
  },
  error: function () {
    alert('An error occurred. Please try again.');
  }
});
    });
  });
</script>
</body>
</html>
