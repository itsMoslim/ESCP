<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Coach");
require_once "db_connect.php";

$coach_id   = $_SESSION['profile_id'];
$session_id = intval($_GET['solo_session_id'] ?? 0);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['feedback_submit'])) {
        $rating         = $_POST['rating'];
        $numeric_rating = floatval($_POST['numeric_rating']);
        $comments       = trim($_POST['comments']);

        // Get player_id from session
        $stmt = $conn->prepare("SELECT player_profile_id FROM solo_sessions WHERE solo_session_id=?");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $player_id = $stmt->get_result()->fetch_assoc()['player_profile_id'];

        $insert = $conn->prepare("
            INSERT INTO coach_feedback (session_id, session_type, coach_id, player_id, rating, numeric_rating, comments)
            VALUES (?, 'Solo', ?, ?, ?, ?, ?)
        ");
        $insert->bind_param("iiisds", $session_id, $coach_id, $player_id, $rating, $numeric_rating, $comments);
        $insert->execute();

        header("Location: solo_session_detail.php?solo_session_id=$session_id");
        exit();
    }

    if (isset($_POST['status_update'])) {
        $action = $_POST['action'];
        $update = $conn->prepare("UPDATE solo_sessions SET status=? WHERE solo_session_id=? AND coach_profile_id=?");
        $update->bind_param("sii", $action, $session_id, $coach_id);
        $update->execute();

        header("Location: solo_session_detail.php?solo_session_id=$session_id");
        exit();
    }
}

// Fetch session
$stmt = $conn->prepare("
    SELECT s.*, g.name AS game_name, p.username AS player_name
    FROM solo_sessions s
    JOIN games g ON s.game_id = g.game_id
    LEFT JOIN profiles p ON s.player_profile_id = p.profile_id
    WHERE s.solo_session_id = ? AND s.coach_profile_id = ?
");
$stmt->bind_param("ii", $session_id, $coach_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

// Fetch feedback
$feedbackStmt = $conn->prepare("
    SELECT cf.*, p.username AS player_name
    FROM coach_feedback cf
    JOIN profiles p ON cf.player_id = p.profile_id
    WHERE cf.session_id = ? AND cf.session_type = 'Solo'
");
$feedbackStmt->bind_param("i", $session_id);
$feedbackStmt->execute();
$feedbackResult = $feedbackStmt->get_result();

// Fetch reviews
$reviewStmt = $conn->prepare("
    SELECT r.*, p.username AS player_name
    FROM reviews r
    JOIN profiles p ON r.player_profile_id = p.profile_id
    WHERE r.session_id = ? AND r.session_type = 'Solo'
");
$reviewStmt->bind_param("i", $session_id);
$reviewStmt->execute();
$reviewResult = $reviewStmt->get_result();
?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <div class="page-content">
                <div class="main-banner">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="header-text">
                                <h6><em>Session Detail</em></h6>
                                <h4>Solo Session</h4>
                                <div class="line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="game-details">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2>Welcome Coach <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></h2>
                        </div>
                        <div class="col-lg-12">
                            <div class="content">
                                <div class="row">
                                    <!-- Left Column -->
                                    <div class="col-lg-8">
                                        <div class="left-info">
                                            <h4>Session Information</h4><br>
                                            <p><strong>Game:</strong> <?php echo htmlspecialchars($session['game_name']); ?></p>
                                            <p><strong>Date:</strong> <?php echo htmlspecialchars($session['session_date']); ?></p>
                                            <p><strong>Time:</strong> <?php echo htmlspecialchars($session['start_time']); ?> - <?php echo htmlspecialchars($session['end_time']); ?></p>
                                            <p><strong>Hourly Rate:</strong> <?php echo htmlspecialchars($session['hourly_rate']); ?> SAR</p>
                                            <p><strong>Status:</strong> <?php echo htmlspecialchars($session['status']); ?></p>

                                            <hr>
                                            <h4>Coach Feedback</h4><br>
                                            <?php
                                            switch ($session['status']) {
                                                case 'Available':
                                                case 'Cancelled':
                                                    echo "<p>---</p>";
                                                    break;

                                                case 'Requested':
                                                    echo "<p>---</p>";
                                                    // Approve (make available) or reject (cancel) request
                                                    echo "<form method='POST'>
                                                            <input type='hidden' name='status_update' value='1'>
                                                            <input type='hidden' name='action' value='Available'>
                                                            <button type='submit' class='main-button'>Approve Session</button>
                                                          </form><br>
                                                          <form method='POST'>
                                                            <input type='hidden' name='status_update' value='1'>
                                                            <input type='hidden' name='action' value='Cancelled'>
                                                            <button type='submit' class='main-button'>Reject Session</button>
                                                          </form>";
                                                    break;

                                                case 'Confirmed':
                                                    if ($feedbackResult->num_rows > 0) {
                                                        while ($fb = $feedbackResult->fetch_assoc()) {
                                                            echo "<p><strong>" . htmlspecialchars($fb['player_name']) . ":</strong>
                                                                  Rating " . htmlspecialchars($fb['numeric_rating']) . " (" . htmlspecialchars($fb['rating']) . ")<br>
                                                                  Comments: " . htmlspecialchars($fb['comments']) . "</p>";
                                                        }
                                                    } else {
                                                        ?>
                                                        <div class="escp_form">
                                                            <form method="POST">
                                                                <input type="hidden" name="feedback_submit" value="1">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Rating</label>
                                                                    <select name="rating" class="form-control" required>
                                                                        <option value="">-- Select Rating --</option>
                                                                        <option value="Good">Good</option>
                                                                        <option value="Average">Average</option>
                                                                        <option value="Below Average">Below Average</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Numeric Rating (1-5)</label>
                                                                    <input type="number" name="numeric_rating" min="1" max="5" class="form-control" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Comments</label>
                                                                    <textarea name="comments" class="form-control" rows="4"></textarea>
                                                                </div>
                                                                <button type="submit" class="main-button">
                                                                    <i class="fa fa-paper-plane"></i> Submit Feedback
                                                                </button>
                                                            </form>
                                                        </div><br>
                                                        <?php
                                                    }
                                                    // Mark Completed button
                                                    echo "<form method='POST'>
                                                            <input type='hidden' name='status_update' value='1'>
                                                            <input type='hidden' name='action' value='Completed'>
                                                            <button type='submit' class='main-button'>Mark Completed</button>
                                                          </form>";
                                                    break;

                                                case 'Completed':
                                                    if ($feedbackResult->num_rows > 0) {
                                                        while ($fb = $feedbackResult->fetch_assoc()) {
                                                            echo "<p><strong>" . htmlspecialchars($fb['player_name']) . ":</strong>
                                                                  Rating " . htmlspecialchars($fb['numeric_rating']) . " (" . htmlspecialchars($fb['rating']) . ")<br>
                                                                  Comments: " . htmlspecialchars($fb['comments']) . "</p>";
                                                        }
                                                    } else {
                                                        echo "<p>---</p>";
                                                    }
                                                    break;

                                                default:
                                                    echo "<p>---</p>";
                                                    break;
                                            }
                                            ?>

                                            <hr>
                                            <h4>Player Reviews</h4><br>
                                            <?php if ($reviewResult->num_rows > 0): ?>
                                                <?php while ($rv = $reviewResult->fetch_assoc()): ?>
                                                    <p><strong><?php echo htmlspecialchars($rv['player_name']); ?>:</strong>
                                                        Rating <?php echo htmlspecialchars($rv['rating']); ?><br>
                                                        Comments: <?php echo htmlspecialchars($rv['comments']); ?></p>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <div class="alert alert-info">No reviews yet.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="col-lg-4">
                                        <div class="right-info">
                                            <h4>Control Hub</h4>
                                            <?php include 'c_menu.php'; ?>
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
