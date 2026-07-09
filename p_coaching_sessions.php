<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Player");
require_once "db_connect.php";

$player_id = $_SESSION['profile_id'];
$showBookingConfirmed = (($_GET['booking'] ?? '') === 'confirmed');

// --- Fetch Solo Sessions (coach-created or player-requested) ---
$soloStmt = $conn->prepare("
    SELECT s.*, g.name AS game_name, COALESCE(c.fullname, pc.username) AS coach_name
    FROM solo_sessions s
    JOIN games g ON s.game_id = g.game_id
    JOIN profiles pc ON s.coach_profile_id = pc.profile_id
    LEFT JOIN coaches c ON pc.username = c.username
    WHERE s.player_profile_id = ?
    ORDER BY s.session_date DESC
");
$soloStmt->bind_param("i", $player_id);
$soloStmt->execute();
$soloResult = $soloStmt->get_result();

// --- Fetch Group Sessions (player enrolled) ---
$groupStmt = $conn->prepare("
    SELECT gs.*, g.name AS game_name, COALESCE(c.fullname, pc.username) AS coach_name
    FROM group_sessions gs
    JOIN games g ON gs.game_id = g.game_id
    JOIN profiles pc ON gs.coach_profile_id = pc.profile_id
    LEFT JOIN coaches c ON pc.username = c.username
    JOIN group_enrollments ge ON gs.session_id = ge.session_id
    WHERE ge.player_profile_id = ?
    ORDER BY gs.session_date DESC
");
$groupStmt->bind_param("i", $player_id);
$groupStmt->execute();
$groupResult = $groupStmt->get_result();
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
                                <h6><em>My Coaching Sessions</em></h6>
                                <h4>Solo & Group</h4>
                                <div class="line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="game-details">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2>Welcome <?php echo htmlspecialchars($_SESSION['username'] ?? 'Player'); ?></h2>
                            <?php if ($showBookingConfirmed): ?>
                                <div id="booking-confirmed-alert" class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                                    Session Booking Confirmed! Please check your email for further details.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-12">
                            <div class="content">
                                <div class="row">
                                    <!-- Left Column: Sessions Info -->
                                    <div class="col-lg-8">
                                        <div class="left-info">
                                            <h3>Sessions Info</h3><br>

                                            <!-- Solo Sessions -->
                                            <div class="escp_content1">
                                                <div class="heading-section">
                                                    <h4><em>Solo Sessions</em></h4>
                                                </div>
                                                <div class="row">
                                                    <?php if ($soloResult->num_rows > 0): ?>
                                                        <?php while ($ss = $soloResult->fetch_assoc()): ?>
                                                            <div class="col-lg-6">
                                                                <div class="item">
                                                                    <h5><?php echo htmlspecialchars($ss['session_date']); ?><br>
                                                                        <?php echo htmlspecialchars($ss['start_time']); ?> - <?php echo htmlspecialchars($ss['end_time']); ?></h5>
                                                                    <p>Game: <?php echo htmlspecialchars($ss['game_name']); ?></p>
                                                                    <p>Coach: <?php echo htmlspecialchars($ss['coach_name']); ?></p>
                                                                    <p>Fee: <?php echo htmlspecialchars($ss['hourly_rate']); ?> SAR</p>
                                                                    <p>Status: <?php echo htmlspecialchars($ss['status']); ?></p><br><br>

                                                                    <?php if ($ss['status'] === 'Requested' && !empty($ss['player_profile_id'])): ?>
                                                                        <p><em>Waiting for coach reply</em></p>

                                                                    <?php elseif ($ss['status'] === 'Available' && !empty($ss['player_profile_id'])): ?>

                                                                        <a href="book_solo.php?solo_session_id=<?php echo (int)$ss['solo_session_id']; ?>">
                                                                            Book Now
                                                                        </a>


                                                                    <?php elseif ($ss['status'] === 'Completed'): ?>

                                                                        <a href="p_session_feedback_review.php?solo_session_id=<?php echo (int)$ss['solo_session_id']; ?>&type=Solo">
                                                                            View Feedback & Provide Review
                                                                        </a>

                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <div class="alert alert-info">No solo sessions found.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Group Sessions -->
                                            <div class="escp_content1">
                                                <div class="heading-section">
                                                    <h4><em>Group Sessions</em></h4>
                                                </div>
                                                <div class="row">
                                                    <?php if ($groupResult->num_rows > 0): ?>
                                                        <?php while ($gs = $groupResult->fetch_assoc()): ?>
                                                            <div class="col-lg-6">
                                                                <div class="item">
                                                                    <h5><?php echo htmlspecialchars($gs['training_detail']); ?></h5>
                                                                    <p><?php echo htmlspecialchars($gs['session_date']); ?><br>
                                                                        <?php echo htmlspecialchars($gs['start_time']); ?> - <?php echo htmlspecialchars($gs['end_time']); ?></p>
                                                                    <p>Game: <?php echo htmlspecialchars($gs['game_name']); ?></p>
                                                                    <p>Coach: <?php echo htmlspecialchars($gs['coach_name']); ?></p>
                                                                    <p>Fee: <?php echo htmlspecialchars($gs['fee']); ?> SAR</p>
                                                                    <p>Status: <?php echo htmlspecialchars($gs['status']); ?></p><br><br>

                                                                    <?php if ($gs['status'] === 'Completed' ): ?>

                                                                         <a href="p_session_feedback_review.php?group_session_id=<?php echo (int)$gs['session_id']; ?>&type=Group">
                                                                           View Feedback & Provide Review
                                                                        </a>

                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <div class="alert alert-info">No group sessions found.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
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

<?php if ($showBookingConfirmed): ?>
<script>
setTimeout(function () {
    var alertEl = document.getElementById('booking-confirmed-alert');
    if (!alertEl) return;

    if (window.bootstrap && window.bootstrap.Alert) {
        var bsAlert = window.bootstrap.Alert.getOrCreateInstance(alertEl);
        bsAlert.close();
    } else {
        alertEl.style.display = 'none';
    }
}, 5000);
</script>
<?php endif; ?>