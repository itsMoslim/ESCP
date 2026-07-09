<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Admin");
require_once 'db_connect.php';

$error = "";

$username = $_GET['username'] ?? '';
if (empty($username)) {
  $error = "No coach username provided.";
} else {
  // Fetch coach details
  $stmt = $conn->prepare("
        SELECT c.username, c.fullname, c.email, c.account_active, c.verification_flag, c.hourly_rate, c.created_at,
               p.profile_id, p.bio, p.experience_years, p.education, p.rating, p.profile_picture,
               ug.rank_id, ug.role_id, g.name AS game_name
        FROM coaches c
        JOIN profiles p ON c.username = p.username
        LEFT JOIN user_games ug ON p.profile_id = ug.profile_id
        LEFT JOIN games g ON ug.game_id = g.game_id
        WHERE c.username = ?
    ");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $coach = $stmt->get_result()->fetch_assoc();

  if (!$coach) {
    $error = "Coach not found.";
  } else {
    // Fetch rank and role names if available
    $rankName = "";
    $roleName = "";
    if (!empty($coach['rank_id'])) {
      $r = $conn->query("SELECT rank_name FROM game_ranks WHERE rank_id=" . (int)$coach['rank_id'])->fetch_assoc();
      $rankName = $r['rank_name'] ?? "";
    }
    if (!empty($coach['role_id'])) {
      $r = $conn->query("SELECT role_name FROM game_roles WHERE role_id=" . (int)$coach['role_id'])->fetch_assoc();
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
                <h6><em>Review coach profile details.</em></h6>
                <h4>Coach Profile</h4>
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
                  <!-- Left Column: Coach Profile -->
                  <div class="col-lg-8">
                    <div class="left-info">
                      <h4>Profile Information</h4><br><br><br>

                      <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                      <?php else: ?>
                        <h5><?php echo htmlspecialchars($coach['fullname']); ?></h5><br>
                        <h6>Username: <?php echo htmlspecialchars($coach['username']); ?></h6>
                        <h6>Email: <?php echo htmlspecialchars($coach['email']); ?></h6>
                        <p><small>Joined: <?php echo $coach['created_at']; ?></small></p><br>
                        <p>Status: <?php echo $coach['account_active'] ? "Active" : "Inactive"; ?></p>
                        <p>Verification: <?php echo $coach['verification_flag'] ? "Verified" : "Not Verified"; ?></p>
                        <?php if (!empty($coach['hourly_rate'])): ?>
                          <p>Hourly Rate: <?php echo htmlspecialchars($coach['hourly_rate']); ?></p>
                        <?php endif; ?>
                        <hr>

                        <?php if (!empty($coach['profile_picture'])): ?>
                          <img src="assets/pictures/<?php echo htmlspecialchars($coach['profile_picture']); ?>" alt="Profile Picture" width="150"><br><br>
                        <?php endif; ?>

                        <p><strong>Bio:</strong> <?php echo nl2br(htmlspecialchars($coach['bio'])); ?></p>
                        <p><strong>Experience:</strong> <?php echo (int)$coach['experience_years']; ?> years</p>
                        <p><strong>Education:</strong> <?php echo htmlspecialchars($coach['education']); ?></p>
                        <p><strong>Rating:</strong> <?php echo htmlspecialchars($coach['rating']); ?>/5</p>
                        <hr>

                        <?php if (!empty($coach['game_name'])): ?>
                          <p><strong>Game:</strong> <?php echo htmlspecialchars($coach['game_name']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($rankName)): ?>
                          <p><strong>Rank:</strong> <?php echo htmlspecialchars($rankName); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($roleName)): ?>
                          <p><strong>Role:</strong> <?php echo htmlspecialchars($roleName); ?></p>
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