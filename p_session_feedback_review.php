<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Player");
require_once "db_connect.php";

$player_id = $_SESSION['profile_id'];
$session_type = $_GET['type'] ?? 'Solo';

// Detect session id based on type
if ($session_type === 'Solo') {
    $session_id = intval($_GET['solo_session_id'] ?? 0);
} else {
    $session_id = intval($_GET['group_session_id'] ?? 0);
}

// --- Fetch Coach Feedback ---
$feedbackStmt = $conn->prepare("
    SELECT cf.*, pc.username AS coach_name
    FROM coach_feedback cf
    JOIN profiles pc ON cf.coach_id = pc.profile_id
    WHERE cf.session_id = ? AND cf.session_type = ? AND cf.player_id = ?
");
$feedbackStmt->bind_param("isi", $session_id, $session_type, $player_id);
$feedbackStmt->execute();
$feedback = $feedbackStmt->get_result()->fetch_assoc();

// --- Check if player already reviewed this session ---
$reviewStmt = $conn->prepare("
    SELECT r.*, pc.username AS coach_name
    FROM reviews r
    JOIN profiles pc ON r.coach_profile_id = pc.profile_id
    WHERE r.session_id = ? AND r.session_type = ? AND r.player_profile_id = ?
");
$reviewStmt->bind_param("isi", $session_id, $session_type, $player_id);
$reviewStmt->execute();
$existingReview = $reviewStmt->get_result()->fetch_assoc();

// --- Handle Review Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingReview) {
    $rating = intval($_POST['rating']);
    $comments = $_POST['comments'];

    // If feedback exists, use coach_id from feedback; otherwise, fetch coach from session
    if ($feedback) {
        $coach_id = $feedback['coach_id'];
    } else {
        if ($session_type === 'Solo') {
            $coachQuery = $conn->prepare("SELECT coach_profile_id FROM solo_sessions WHERE solo_session_id = ?");
        } else {
            $coachQuery = $conn->prepare("SELECT coach_profile_id FROM group_sessions WHERE session_id = ?");
        }
        $coachQuery->bind_param("i", $session_id);
        $coachQuery->execute();
        $coach_id = $coachQuery->get_result()->fetch_assoc()['coach_profile_id'];
    }

    $insertStmt = $conn->prepare("
        INSERT INTO reviews (session_id, session_type, coach_profile_id, player_profile_id, rating, comments) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->bind_param("isiiss", $session_id, $session_type, $coach_id, $player_id, $rating, $comments);
    $insertStmt->execute();

    // Update coach average rating
    $updateStmt = $conn->prepare("
        UPDATE profiles 
        SET rating = (SELECT ROUND(AVG(rating),2) FROM reviews WHERE coach_profile_id = ?) 
        WHERE profile_id = ?
    ");
    $updateStmt->bind_param("ii", $coach_id, $coach_id);
    $updateStmt->execute();

    header("Location: p_coaching_sessions.php");
    exit();
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
                                <h6><em>Session Feedback & Review</em></h6>
                                <h4>Coach Feedback & Your Review</h4>
                                <div class="line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="game-details">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2>Welcome <?php echo htmlspecialchars($_SESSION['username'] ?? 'Player'); ?></h2>
                        </div>
                        <div class="col-lg-12">
                            <div class="content">
                                <div class="row">
                                    <!-- Left Column: Sessions Feedback and Review -->
                                    <div class="col-lg-8">
                                        <div class="left-info">
                                            <h4>Coach Feedback</h4><br>
                                            <?php if ($feedback): ?>
                                                <p><strong>Coach:</strong> <?php echo htmlspecialchars($feedback['coach_name']); ?></p>
                                                <p><strong>Rating:</strong> <?php echo htmlspecialchars($feedback['rating']); ?> (<?php echo htmlspecialchars($feedback['numeric_rating']); ?>)</p>
                                                <p><strong>Comments:</strong> <?php echo htmlspecialchars($feedback['comments']); ?></p>
                                            <?php else: ?>
                                                <div class="alert alert-info">No feedback yet.</div>
                                            <?php endif; ?>

                                            <hr>
                                            <h4>Your Review</h4><br>
                                            <?php if ($existingReview): ?>
                                                <p><strong>Your Rating:</strong> <?php echo htmlspecialchars($existingReview['rating']); ?></p><br>
                                                <p><strong>Your Comments:</strong> <?php echo htmlspecialchars($existingReview['comments']); ?></p>
                                            <?php else: ?>
                                                <form method="POST" class="escp_form">
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
                                                        <i class="fa fa-paper-plane"></i> Submit Review
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Right Column: Control Hub -->
                                    <div class="col-lg-4">
                                        <div class="right-info">
                                            <h4>Control Hub</h4>
                                            <?php include 'p_menu.php'; ?>
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