<?php
include 'header.php';
require_once 'db_connect.php';

// Helper function to render error page consistently
function renderError($message) {
    ?>
    <div class="container">
      <div class="page-content">
        <!-- Banner -->
        <div class="main-banner">
          <div class="header-text">
            <h6><em>Request a Solo Session</em></h6>
            <h4>Customize Your Availability</h4>
            <div class="line"></div>
          </div>
        </div>

        <!-- Error Message -->
        <div class="escp_form">
          <div class="container my-5">
            <div class="row justify-content-center">
              <div class="col-lg-8">
                <div class="alert alert-danger text-center">
                  <?php echo htmlspecialchars($message); ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    include 'footer.php';
    exit();
}

// Access control
if ($_SESSION['role'] !== 'Player') {
    renderError("Only players can request sessions.");
}

$coach_id = intval($_GET['id'] ?? 0);   // profile_id of coach
$player_id = $_SESSION['profile_id'];

// Fetch coach hourly rate by joining profiles → coaches
$coachStmt = $conn->prepare("
    SELECT c.hourly_rate 
    FROM profiles p 
    JOIN coaches c ON p.username = c.username 
    WHERE p.profile_id = ?
");
$coachStmt->bind_param("i", $coach_id);
$coachStmt->execute();
$coachRateResult = $coachStmt->get_result();
if ($coachRateResult->num_rows === 0) {
    renderError("Coach not found or hourly rate unavailable.");
}
$coachRate = $coachRateResult->fetch_assoc()['hourly_rate'];

// Fetch games for dropdown
$gamesResult = $conn->query("SELECT game_id, name FROM games ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['session_date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $game_id = intval($_POST['game_id']);

    $stmt = $conn->prepare("INSERT INTO solo_sessions 
        (coach_profile_id, player_profile_id, game_id, session_date, start_time, end_time, hourly_rate, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Requested')");
    $stmt->bind_param("iiisssd", $coach_id, $player_id, $game_id, $date, $start, $end, $coachRate);
    $stmt->execute();

    header("Location: coachprofile.php?id=" . $coach_id);
    exit();
}
?>

<div class="container">
  <div class="page-content">
    <!-- Banner -->
    <div class="main-banner">
      <div class="header-text">
        <h6><em>Request a Solo Session</em></h6>
        <h4>Customize Your Availability</h4>
        <div class="line"></div>
      </div>
    </div>

    <!-- Form -->
    <div class="escp_form">
      <div class="container my-5">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <form method="POST">
              <div class="mb-3">
                <label class="form-label">Session Date</label>
                <input type="date" name="session_date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Start Time</label>
                <input type="time" name="start_time" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">End Time</label>
                <input type="time" name="end_time" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Game</label>
                <select name="game_id" class="form-control" required>
                  <option value="">-- Select Game --</option>
                  <?php while($g = $gamesResult->fetch_assoc()): ?>
                    <option value="<?php echo $g['game_id']; ?>">
                      <?php echo htmlspecialchars($g['name']); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Hourly Rate (SAR)</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($coachRate); ?>" disabled>
              </div>
              <button type="submit" class="main-button">
                <i class="fa fa-calendar-plus"></i> Submit Request
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
