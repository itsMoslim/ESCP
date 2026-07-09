<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Coach");
require_once "db_connect.php";

$coach_id   = $_SESSION['profile_id'];
$session_id = intval($_GET['session_id'] ?? 0);
$player_id  = intval($_GET['player_id'] ?? 0);
$session_type = $_GET['type'] ?? 'Group';

// --- Fetch Player Info ---
$playerStmt = $conn->prepare("SELECT username FROM profiles WHERE profile_id = ?");
$playerStmt->bind_param("i", $player_id);
$playerStmt->execute();
$player = $playerStmt->get_result()->fetch_assoc();

// --- Fetch Existing Feedback ---
$feedbackStmt = $conn->prepare("
    SELECT * FROM coach_feedback 
    WHERE session_id=? AND session_type=? AND coach_id=? AND player_id=?
");
$feedbackStmt->bind_param("isii", $session_id, $session_type, $coach_id, $player_id);
$feedbackStmt->execute();
$existingFeedback = $feedbackStmt->get_result()->fetch_assoc();

$error = "";
$success = "";

// --- Handle Feedback Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingFeedback) {
    $rating   = intval($_POST['rating']);
    $comments = trim($_POST['comments']);

    try {
        $insertStmt = $conn->prepare("
            INSERT INTO coach_feedback (session_id, session_type, coach_id, player_id, rating, comments)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->bind_param("isiiss", $session_id, $session_type, $coach_id, $player_id, $rating, $comments);
        $insertStmt->execute();

        // Update player average rating
        $updateStmt = $conn->prepare("
            UPDATE profiles 
            SET rating = (SELECT ROUND(AVG(rating),2) FROM coach_feedback WHERE player_id = ?)
            WHERE profile_id = ?
        ");
        $updateStmt->bind_param("ii", $player_id, $player_id);
        $updateStmt->execute();

        $success = "Feedback submitted successfully.";
        header("Location: c_session_detail.php?group_session_id=$session_id&type=Group");
        exit();
    } catch (Exception $e) {
        $error = "Failed to submit feedback: " . $e->getMessage();
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
                                <h6><em>Provide Feedback</em></h6>
                                <h4>Group Session Feedback</h4>
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
                                            <h4>Feedback for Player: <?php echo htmlspecialchars($player['username']); ?></h4><br>

                                            <?php if (!empty($error)): ?>
                                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                            <?php elseif (!empty($success)): ?>
                                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                            <?php endif; ?>

                                            <?php if ($existingFeedback): ?>
                                                <p><strong>Rating:</strong> <?php echo htmlspecialchars($existingFeedback['rating']); ?></p>
                                                <p><strong>Comments:</strong> <?php echo htmlspecialchars($existingFeedback['comments']); ?></p>
                                            <?php else: ?>
                                                <div class="escp_form">
                                                    <form method="POST" enctype="multipart/form-data">
                                                        <div class="mb-3">
                                                            <label class="form-label">Rating (1-5)</label>
                                                            <select name="rating" class="form-control" required>
                                                                <option value="">-- Select Rating --</option>
                                                                <option value="1">1 - Poor</option>
                                                                <option value="2">2 - Fair</option>
                                                                <option value="3">3 - Average</option>
                                                                <option value="4">4 - Good</option>
                                                                <option value="5">5 - Excellent</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Comments</label>
                                                            <textarea name="comments" class="form-control" rows="4" required></textarea>
                                                        </div>
                                                        <button type="submit" class="main-button">
                                                            <i class="fa fa-paper-plane"></i> Submit Feedback
                                                        </button>
                                                    </form>
                                                </div>
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

<?php
include 'footer.php';
?>
