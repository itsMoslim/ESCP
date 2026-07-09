<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Coach");
require_once "db_connect.php";

$coach_id   = $_SESSION['profile_id'];
$session_id = intval($_GET['group_session_id'] ?? 0);

// Handle POST actions (feedback or status update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['feedback_submit'])) {
        $player_id      = intval($_POST['player_id']);
        $rating         = $_POST['rating'];
        $numeric_rating = floatval($_POST['numeric_rating']);
        $comments       = trim($_POST['comments']);

        $insert = $conn->prepare("
            INSERT INTO coach_feedback (session_id, session_type, coach_id, player_id, rating, numeric_rating, comments)
            VALUES (?, 'Group', ?, ?, ?, ?, ?)
        ");
        $insert->bind_param("iiisds", $session_id, $coach_id, $player_id, $rating, $numeric_rating, $comments);
        $insert->execute();

        header("Location: group_session_detail.php?group_session_id=$session_id");
        exit();
    }

    if (isset($_POST['status_update'])) {
        $action = $_POST['action'];
        $update = $conn->prepare("UPDATE group_sessions SET status=? WHERE session_id=? AND coach_profile_id=?");
        $update->bind_param("sii", $action, $session_id, $coach_id);
        $update->execute();

        header("Location: group_session_detail.php?group_session_id=$session_id");
        exit();
    }
}

// Fetch session
$stmt = $conn->prepare("
    SELECT s.*, g.name AS game_name
    FROM group_sessions s
    JOIN games g ON s.game_id = g.game_id
    WHERE s.session_id = ? AND s.coach_profile_id = ?
");
$stmt->bind_param("ii", $session_id, $coach_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    echo $session_id;
    echo  $coach_id;
    echo "<div class='alert alert-danger'>Session not found or you do not have access.</div>";
    include 'footer.php';
    exit();
}



// Fetch participants
$participantsStmt = $conn->prepare("
    SELECT e.*, p.username AS player_name, p.profile_id
    FROM group_enrollments e
    JOIN profiles p ON e.player_profile_id = p.profile_id
    WHERE e.session_id = ?
");
$participantsStmt->bind_param("i", $session_id);
$participantsStmt->execute();
$participants = $participantsStmt->get_result();

// Fetch reviews
$reviewStmt = $conn->prepare("
    SELECT r.*, p.username AS player_name
    FROM reviews r
    JOIN profiles p ON r.player_profile_id = p.profile_id
    WHERE r.session_id = ? AND r.session_type = 'Group'
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
                                <h4>Group Session</h4>
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
                                            <p><strong>Fee:</strong> <?php echo htmlspecialchars($session['fee']); ?> SAR</p>
                                            <p><strong>Participants:</strong> Min <?php echo htmlspecialchars($session['min_participants']); ?> / Max <?php echo htmlspecialchars($session['max_participants']); ?></p>
                                            <p><strong>Status:</strong> <?php echo htmlspecialchars($session['status']); ?></p>

                                            <hr>
                                            <h4>Participants & Feedback</h4><br>
                                            <?php if ($participants->num_rows > 0): ?>
                                                <?php while ($p = $participants->fetch_assoc()): ?>
                                                    <p><strong><?php echo htmlspecialchars($p['player_name']); ?>:</strong>
                                                        <?php
                                                        $playerFeedback = $conn->prepare("
                                                        SELECT * FROM coach_feedback WHERE session_id=? AND session_type='Group' AND player_id=?
                                                    ");
                                                        $playerFeedback->bind_param("ii", $session_id, $p['profile_id']);
                                                        $playerFeedback->execute();
                                                        $pfResult = $playerFeedback->get_result();

                                                        if ($pfResult->num_rows > 0) {
                                                            $fb = $pfResult->fetch_assoc();
                                                            echo "Rating " . htmlspecialchars($fb['numeric_rating']) . " (" . htmlspecialchars($fb['rating']) . ")<br>
                                                              Comments: " . htmlspecialchars($fb['comments']);
                                                        } elseif ($session['status'] === 'Ongoing') {
                                                        ?>
                                                    <div class="escp_form">
                                                        <form method="POST">
                                                            <input type="hidden" name="feedback_submit" value="1">
                                                            <input type="hidden" name="player_id" value="<?php echo (int)$p['profile_id']; ?>">
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
                                                                <textarea name="comments" class="form-control" rows="3"></textarea>
                                                            </div>
                                                            <button type="submit" class="main-button">Submit Feedback</button>
                                                        </form>
                                                    </div>
                                                <?php
                                                        } else {
                                                            echo "---";
                                                        }
                                                ?>
                                                </p>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <?php if ($session['status'] === 'Scheduled'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="status_update" value="1">
                                                    <input type="hidden" name="action" value="Cancelled">
                                                    <button type="submit" class="main-button">Cancel Session</button>
                                                </form>
                                            <?php else: ?>
                                                <p>No participants enrolled yet.</p>
                                            <?php endif; ?>
                                        <?php endif; ?>

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

                                        <hr>
                                        <h4>Session Actions</h4><br>
                                        <?php
                                        switch ($session['status']) {
                                            case 'Scheduled':
                                                if ($participants->num_rows > 0) {
                                                    // Participants exist → allow starting session
                                                    echo "<form method='POST'>
                    <input type='hidden' name='status_update' value='1'>
                    <input type='hidden' name='action' value='Ongoing'>
                    <button type='submit' class='main-button'>Start Session</button>
                  </form>";
                                                } else {
                                                    // No participants → allow cancel
                                                    echo "<form method='POST'>
                    <input type='hidden' name='status_update' value='1'>
                    <input type='hidden' name='action' value='Cancelled'>
                    <button type='submit' class='main-button'>Cancel Session</button>
                  </form>";
                                                }
                                                break;

                                            case 'Ongoing':
                                                echo "<form method='POST'>
                <input type='hidden' name='status_update' value='1'>
                <input type='hidden' name='action' value='Completed'>
                <button type='submit' class='main-button'>Mark Completed</button>
              </form>";
                                                break;

                                            case 'Completed':
                                            case 'Cancelled':
                                                echo "<p>Status: " . htmlspecialchars($session['status']) . "</p>";
                                                break;

                                            default:
                                                echo "<p>---</p>";
                                                break;
                                        }
                                        ?>

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