<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Coach");
require_once "db_connect.php";

$coach_id = $_SESSION['profile_id'];

// --- Fetch Solo Sessions (created by coach) ---
$soloStmt = $conn->prepare("
    SELECT s.*, g.name AS game_name, pp.username AS player_name
    FROM solo_sessions s
    JOIN games g ON s.game_id = g.game_id
    LEFT JOIN profiles pp ON s.player_profile_id = pp.profile_id
    WHERE s.coach_profile_id = ?
    ORDER BY s.session_date DESC
");
$soloStmt->bind_param("i", $coach_id);
$soloStmt->execute();
$soloResult = $soloStmt->get_result();

// --- Fetch Group Sessions (created by coach) ---
$groupStmt = $conn->prepare("
    SELECT gs.*, g.name AS game_name, COUNT(ge.enrollment_id) AS enrolled_count
    FROM group_sessions gs
    JOIN games g ON gs.game_id = g.game_id
    LEFT JOIN group_enrollments ge ON gs.session_id = ge.session_id
    WHERE gs.coach_profile_id = ?
    GROUP BY gs.session_id
    ORDER BY gs.session_date DESC
");
$groupStmt->bind_param("i", $coach_id);
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
                            <h2>Welcome Coach <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></h2>
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
                                                    <a href="create_solo.php" class="main-button">Create Solo Session</a><br><br>
                                                </div>
                                                <div class="row">
                                                    <?php if ($soloResult->num_rows > 0): ?>
                                                        <?php while ($ss = $soloResult->fetch_assoc()): ?>
                                                            <div class="col-lg-6">
                                                                <div class="item">
                                                                    <h5><?php echo htmlspecialchars($ss['session_date']); ?><br>
                                                                        <?php echo htmlspecialchars($ss['start_time']); ?> - <?php echo htmlspecialchars($ss['end_time']); ?></h5>
                                                                    <p>Game: <?php echo htmlspecialchars($ss['game_name']); ?></p>
                                                                    <p>Player: <?php echo htmlspecialchars($ss['player_name'] ?? 'Not Assigned'); ?></p>
                                                                    <p>Fee: <?php echo htmlspecialchars($ss['hourly_rate']); ?> SAR</p>
                                                                    <p>Status: <?php echo htmlspecialchars($ss['status']); ?></p><br><br>

                                                                    <?php if ($ss['status'] === 'Requested'): ?>
                                                                        <a href="solo_session_detail.php?solo_session_id=<?php echo (int)$ss['solo_session_id']; ?>&type=Solo">
                                                                            Session Details
                                                                        </a>

                                                                    <?php elseif ($ss['status'] === 'Confirmed'): ?>
                                                                        <a href="solo_session_detail.php?solo_session_id=<?php echo (int)$ss['solo_session_id']; ?>">
                                                                            View Session Details & Provide Feedback
                                                                        </a>

                                                                    <?php elseif ($ss['status'] === 'Completed'): ?>
                                                                        <a href="solo_session_detail.php?solo_session_id=<?php echo (int)$ss['solo_session_id']; ?>">
                                                                            View Session Details
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
                                                    
                                                        <a href="create_group.php" class="main-button">Create Group Session</a><br><br>
                                                   
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
                                                                    <p>Enrolled Players: <?php echo (int)$gs['enrolled_count']; ?></p>
                                                                    <p>Fee: <?php echo htmlspecialchars($gs['fee']); ?> SAR</p>
                                                                    <p>Status: <?php echo htmlspecialchars($gs['status']); ?></p><br><br>

                                                                    <?php if ($gs['status'] === 'Scheduled'): ?>
                                                                        <a href="group_session_detail.php?group_session_id=<?php echo (int)$gs['session_id']; ?>&type=Group">
                                                                            Manage Session
                                                                        </a>

                                                                    <?php elseif ($gs['status'] === 'Ongoing'): ?>
                                                                        <a href="group_session_detail.php?group_session_id=<?php echo (int)$gs['session_id']; ?>&type=Group">
                                                                            View Participants / Provide Feedback
                                                                        </a>

                                                                    <?php elseif ($gs['status'] === 'Completed'): ?>
                                                                        <a href="group_session_detail.php?group_session_id=<?php echo (int)$gs['session_id']; ?>&type=Group">
                                                                            View Session Details
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