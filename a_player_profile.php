<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Admin");
require_once 'db_connect.php';

$error = "";


$username = $_GET['username'] ?? '';
if (empty($username)) {
    $error = "No player username provided.";
} else {
    // Fetch player details
    $stmt = $conn->prepare("
        SELECT pl.username, pl.fullname, pl.email, pl.account_active, pl.created_at,
               p.profile_id, p.bio, p.experience_years, p.rating, p.profile_picture,
               ug.rank_id, ug.role_id, g.name AS game_name,
               pp.coaching_goal
        FROM players pl
        JOIN profiles p ON pl.username = p.username
        LEFT JOIN user_games ug ON p.profile_id = ug.profile_id
        LEFT JOIN games g ON ug.game_id = g.game_id
        LEFT JOIN player_preferences pp ON p.profile_id = pp.profile_id
        WHERE pl.username = ?
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $player = $stmt->get_result()->fetch_assoc();

    if (!$player) {
        $error = "Player not found.";
    } else {
        // Fetch rank and role names if available
        $rankName = "";
        $roleName = "";
        if (!empty($player['rank_id'])) {
            $r = $conn->query("SELECT rank_name FROM game_ranks WHERE rank_id=".(int)$player['rank_id'])->fetch_assoc();
            $rankName = $r['rank_name'] ?? "";
        }
        if (!empty($player['role_id'])) {
            $r = $conn->query("SELECT role_name FROM game_roles WHERE role_id=".(int)$player['role_id'])->fetch_assoc();
            $roleName = $r['role_name'] ?? "";
        }
    }
}
?>

<div class="container">
  <div class="row">
    <div class="col-lg-12">
      <div class="page-content">

        <!-- Banner -->
        <div class="main-banner">
          <div class="row">
            <div class="col-lg-7">
              <div class="header-text">
                <h6><em>Review player profile details.</em></h6>
                <h4>Player Profile</h4>
                <div class="line"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="game-details">
          <div class="row">
            <div class="col-lg-12">
              <h2>Welcome <?php echo htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?></h2>
            </div>
            <div class="col-lg-12">
              <div class="content">
                <div class="row">
                  <!-- Left Column: Player Profile -->
                  <div class="col-lg-8">
                    <div class="left-info">
                      <h4>Profile Information</h4><br><br><br>

                      <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                      <?php else: ?>
                        <h5><?php echo htmlspecialchars($player['fullname']); ?></h5><br>
                        <h6>Username: <?php echo htmlspecialchars($player['username']); ?></h6>
                        <h6>Email: <?php echo htmlspecialchars($player['email']); ?></h6>
                        <p><small>Joined: <?php echo $player['created_at']; ?></small></p><br>
                        <p>Status: <?php echo $player['account_active'] ? "Active" : "Inactive"; ?></p>
                        <hr>

                        <?php if (!empty($player['profile_picture'])): ?>
                          <img src="assets/pictures/<?php echo htmlspecialchars($player['profile_picture']); ?>" alt="Profile Picture" width="150"><br><br>
                        <?php endif; ?>

                        <p><strong>Bio:</strong> <?php echo nl2br(htmlspecialchars($player['bio'])); ?></p>
                        <p><strong>Experience:</strong> <?php echo (int)$player['experience_years']; ?> years</p>
                        <p><strong>Rating:</strong> <?php echo htmlspecialchars($player['rating']); ?>/5</p>
                        <hr>

                        <?php if (!empty($player['game_name'])): ?>
                          <p><strong>Game:</strong> <?php echo htmlspecialchars($player['game_name']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($rankName)): ?>
                          <p><strong>Rank:</strong> <?php echo htmlspecialchars($rankName); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($roleName)): ?>
                          <p><strong>Role:</strong> <?php echo htmlspecialchars($roleName); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($player['coaching_goal'])): ?>
                          <p><strong>Coaching Goal:</strong> <?php echo nl2br(htmlspecialchars($player['coaching_goal'])); ?></p>
                        <?php endif; ?>
                        <hr>

                        <p><a href="a_user_management.php" class="main-button">Back to User Management</a></p>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Right Column: Control Hub -->
                  <div class="col-lg-4">
                    <div class="right-info">
                      <h4>Control Hub</h4>
                      <?php include 'a_menu.php'; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
