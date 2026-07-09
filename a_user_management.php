<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Admin");
require_once 'db_connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['username'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $action   = $_POST['action'];

        if ($action === 'activate_player') {
            $conn->query("UPDATE players SET account_active = TRUE WHERE username='$username'");
        } elseif ($action === 'deactivate_player') {
            $conn->query("UPDATE players SET account_active = FALSE WHERE username='$username'");
        } elseif ($action === 'activate_coach') {
            // Only verified coaches can be activated
            $conn->query("UPDATE coaches SET account_active = TRUE WHERE username='$username' AND verification_flag = TRUE");
        } elseif ($action === 'deactivate_coach') {
            $conn->query("UPDATE coaches SET account_active = FALSE WHERE username='$username'");
        } elseif ($action === 'verify_coach') {
            $conn->query("UPDATE coaches SET verification_flag = TRUE WHERE username='$username'");
        } elseif ($action === 'reject_coach') {
            // Reject = not verified and not active (using existing columns)
            $conn->query("UPDATE coaches SET verification_flag = FALSE, account_active = FALSE WHERE username='$username'");
        }
    }
}

// Fetch summary metrics
$playersCount = $conn->query("SELECT COUNT(*) AS cnt FROM players")->fetch_assoc()['cnt'];
$coachesCount = $conn->query("SELECT COUNT(*) AS cnt FROM coaches")->fetch_assoc()['cnt'];

// Fetch players
$players = $conn->query("SELECT username, fullname, account_active FROM players");

// Fetch coaches
$coaches = $conn->query("SELECT username, fullname, account_active, verification_flag FROM coaches");
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0"></script>

<div class="container">
  <div class="row">
    <div class="col-lg-12">
      <div class="page-content">

        <!-- Banner -->
        <div class="main-banner">
          <div class="row">
            <div class="col-lg-7">
              <div class="header-text">
                <h6><em>Administrator control and user management.</em></h6>
                <h4>USER MANAGEMENT</h4>
                <div class="line"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="game-details">
          <div class="row">
            <div class="col-lg-12">
              <h2>Welcome <?php echo htmlspecialchars($_SESSION['role'] ?? 'Unknown'); ?></h2>
            </div>

            <div class="col-lg-12">
              <div class="content">
                <div class="row">
                  <!-- User Overview -->
                  <div class="col-lg-6">
                    <div class="left-info">
                      <h4>User Overview</h4>
                      <ul>
                        <li>Total Users: <?php echo (int)$playersCount + (int)$coachesCount; ?></li>
                        <li>Players: <?php echo (int)$playersCount; ?></li>
                        <li>Coaches: <?php echo (int)$coachesCount; ?></li>
                      </ul>
                      <canvas id="userChart" style="max-width:400px; margin-top:20px;"></canvas>
                    </div>
                  </div>

                  <script>
                  const chartColors = ["#7F00F2", "#5800F6"];
                  new Chart(document.getElementById("userChart"), {
                    type: "bar",
                    data: {
                      labels: ["Players", "Coaches"],
                      datasets: [{
                        label: "Counts",
                        data: [<?php echo (int)$playersCount; ?>, <?php echo (int)$coachesCount; ?>],
                        backgroundColor: chartColors
                      }]
                    },
                    options: {
                      responsive: true,
                      plugins: {
                        legend: { labels: { color: "#ffffff" } },
                        title: { display: true, text: "Players vs Coaches", color: "#ffffff" }
                      },
                      scales: {
                        x: { ticks: { color: "#ffffff" }, grid: { color: "rgba(255,255,255,0.2)" } },
                        y: { ticks: { color: "#ffffff" }, grid: { color: "rgba(255,255,255,0.2)" } }
                      }
                    }
                  });
                  </script>

                  <!-- Control Hub -->
                  <div class="col-lg-6">
                    <div class="right-info">
                      <h4>Control Hub</h4>
                      <?php include 'a_menu.php'; ?>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <!-- Players Table -->
                  <div class="col-lg-6">
                    <div class="left-info">
                      <h4>All Players</h4>
                      <table class="theme-table">
                        <thead>
                          <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while($p = $players->fetch_assoc()): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($p['username']); ?></td>
                            <td><?php echo htmlspecialchars($p['fullname']); ?></td>
                            <td>
                              <a href="a_player_profile.php?username=<?php echo urlencode($p['username']); ?>" class="main-button">View Profile</a>
                              <form method="post" style="display:inline;">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($p['username']); ?>">
                                <?php if($p['account_active']): ?>
                                  <button type="submit" name="action" value="deactivate_player" class="main-button">Deactivate</button>
                                <?php else: ?>
                                  <button type="submit" name="action" value="activate_player" class="main-button">Activate</button>
                                <?php endif; ?>
                              </form>
                            </td>
                          </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Coaches Table -->
                  <div class="col-lg-6">
                    <div class="right-info">
                      <h4>All Coaches</h4>
                      <table class="theme-table">
                        <thead>
                          <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while($c = $coaches->fetch_assoc()): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($c['username']); ?></td>
                            <td><?php echo htmlspecialchars($c['fullname']); ?></td>
                            <td>
                              <a href="a_coach_profile.php?username=<?php echo urlencode($c['username']); ?>" class="main-button">View Profile</a>
                              <form method="post" style="display:inline;">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($c['username']); ?>">
                                <?php if($c['account_active']): ?>
                                  <button type="submit" name="action" value="deactivate_coach" class="main-button">Deactivate</button>
                                <?php else: ?>
                                  <?php if($c['verification_flag']): ?>
                                    <button type="submit" name="action" value="activate_coach" class="main-button">Activate</button>
                                  <?php else: ?>
                                    <button type="button" class="main-button" disabled title="Coach must be verified before activation">Activate</button>
                                  <?php endif; ?>
                                <?php endif; ?>
                                <?php if(!$c['verification_flag']): ?>
                                  <button type="submit" name="action" value="verify_coach" class="main-button">Verify</button>
                                  <button type="submit" name="action" value="reject_coach" class="main-button" onclick="return confirm('Reject this coach application? This will deactivate the account and keep it unverified.');">Reject</button>
                                <?php endif; ?>
                              </form>
                            </td>
                          </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
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
