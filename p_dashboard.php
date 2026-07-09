<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Player");
include 'db_connect.php';

$profileId = $_SESSION['profile_id'];

// Fetch profile info with join to players table
$profileResult = $conn->query("SELECT p.*, pl.fullname, pl.email, pref.coaching_goal
                               FROM profiles p
                               LEFT JOIN players pl ON p.username = pl.username
                               LEFT JOIN player_preferences pref ON p.profile_id = pref.profile_id
                               WHERE p.profile_id = $profileId");
$profile = $profileResult ? $profileResult->fetch_assoc() : null;

$showRetentionPrompt = false;
if ($profile) {
    $isNewPlayer = !empty($profile['created_at']) && strtotime($profile['created_at']) >= strtotime('-7 days');

    $lastActivityAt = null;
    $activityStmt = $conn->prepare("
        SELECT MAX(activity_at) AS last_activity
        FROM (
            SELECT MAX(created_at) AS activity_at FROM coach_feedback WHERE player_id = ?
            UNION ALL
            SELECT MAX(attempt_date) AS activity_at FROM quiz_attempts WHERE player_profile_id = ?
            UNION ALL
            SELECT MAX(paid_at) AS activity_at FROM payments WHERE player_profile_id = ?
            UNION ALL
            SELECT MAX(session_date) AS activity_at FROM solo_sessions WHERE player_profile_id = ?
            UNION ALL
            SELECT MAX(gs.session_date) AS activity_at
            FROM group_enrollments ge
            JOIN group_sessions gs ON ge.session_id = gs.session_id
            WHERE ge.player_profile_id = ?
        ) activity_stream
    ");
    $activityStmt->bind_param("iiiii", $profileId, $profileId, $profileId, $profileId, $profileId);
    $activityStmt->execute();
    $activityResult = $activityStmt->get_result()->fetch_assoc();
    $lastActivityAt = $activityResult['last_activity'] ?? null;

    $isInactiveThirtyDays = empty($lastActivityAt) || strtotime($lastActivityAt) < strtotime('-30 days');
    $showRetentionPrompt = $isNewPlayer || $isInactiveThirtyDays;
}


// Fetch games
$gamesResult = $conn->query("SELECT g.name, r.rank_name, ro.role_name
                             FROM user_games ug
                             JOIN games g ON ug.game_id = g.game_id
                             LEFT JOIN game_ranks r ON ug.rank_id = r.rank_id
                             LEFT JOIN game_roles ro ON ug.role_id = ro.role_id
                             WHERE ug.profile_id = $profileId");

// Fetch top 3 feedbacks
$feedbackResult = $conn->query("SELECT rating, numeric_rating, comments, created_at
                                FROM coach_feedback
                                WHERE player_id = $profileId
                                ORDER BY created_at DESC
                                LIMIT 3");

// Ratings for line chart (last 10 sessions)
$ratings = [];
$dates = [];
$ratingResult = $conn->query("SELECT numeric_rating, created_at
                              FROM coach_feedback
                              WHERE player_id = $profileId
                              ORDER BY created_at DESC
                              LIMIT 10");
if ($ratingResult) {
    while ($row = $ratingResult->fetch_assoc()) {
        $ratings[] = $row['numeric_rating'];
        $dates[] = date("M d", strtotime($row['created_at']));
    }
}

// Categorical counts for pie chart
$categories = [];
$counts = [];
$catResult = $conn->query("SELECT rating, COUNT(*) as count
                           FROM coach_feedback
                           WHERE player_id = $profileId
                           GROUP BY rating");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row['rating'];
        $counts[] = $row['count'];
    }
}

// --- Challenge & Badge Logic ---

$profileCompleteId = 1; // "Profile Complete"
$goodBioId = 2;         // "Good Bio"

// Helper: check if profile is complete
function isProfileComplete($profile) {
    return !empty($profile['fullname']) &&
           !empty($profile['email']) &&
           !empty($profile['bio']) &&
           !empty($profile['experience_years']);
}

// Helper: check if bio is "good"
function isGoodBio($bio) {
    return strlen(trim($bio)) >= 50; // example threshold
}

// Maintain permanent badge records
function maintainPermanentBadge($conn, $profileId, $challengeId, $conditionMet) {
    $exists = $conn->query("SELECT id FROM player_challenges_reward WHERE player_profile_id=$profileId AND challenge_id=$challengeId")->fetch_assoc();
    if ($conditionMet) {
        if (!$exists) {
            $conn->query("INSERT INTO player_challenges_reward (player_profile_id, challenge_id, progress_value, challenge_status, reward_status, date_completed) 
                          VALUES ($profileId, $challengeId, NULL, 'Completed', 'Active', NOW())");
        }
    } else {
        if ($exists) {
            $conn->query("DELETE FROM player_challenges_reward WHERE player_profile_id=$profileId AND challenge_id=$challengeId");
        }
    }
}

// Apply permanent badge checks
if ($profile) {
    maintainPermanentBadge($conn, $profileId, $profileCompleteId, isProfileComplete($profile));
    maintainPermanentBadge($conn, $profileId, $goodBioId, isGoodBio($profile['bio']));
}

// Fetch all challenges for this player
$challengeData = $conn->query("
    SELECT c.challenge_id, c.name, c.description, c.status, c.target_value,
           pcr.progress_value, pcr.challenge_status, pcr.reward_status,
           r.badge_image_path, pcr.date_completed
    FROM challenges c
    LEFT JOIN player_challenges_reward pcr ON c.challenge_id = pcr.challenge_id AND pcr.player_profile_id = $profileId
    LEFT JOIN rewards r ON c.challenge_id = r.challenge_id
    ORDER BY c.challenge_id ASC
");

// Handle start challenge button (only for quiz challenges)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_challenge_id'])) {
    $cid = intval($_POST['start_challenge_id']);
    $conn->query("INSERT INTO player_challenges_reward (player_profile_id, challenge_id, progress_value, challenge_status, reward_status) 
                  VALUES ($profileId, $cid, 0, 'Pending', 'Active')
                  ON DUPLICATE KEY UPDATE progress_value=0, challenge_status='Pending', reward_status='Active'");
    header("Location: p_dashboard.php"); // reload
    exit;
}

// Expiry check: mark challenges older than 30 days as expired
if ($challengeData) {
    while ($ch = $challengeData->fetch_assoc()) {
        if (!empty($ch['date_completed'])) {
            $completedDate = strtotime($ch['date_completed']);
            if ($completedDate < strtotime('-30 days')) {
                $conn->query("UPDATE player_challenges_reward 
                              SET reward_status='Expired' 
                              WHERE player_profile_id=$profileId AND challenge_id=".(int)$ch['challenge_id']);
            }
        }
    }
    $challengeData->data_seek(0);
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
                                <h6><em>Player overview and insights.</em></h6>
                                <h4>PLAYER DASHBOARD</h4>
                                <div class="line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="game-details">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2>Welcome <?php echo htmlspecialchars($profile['username'] ?? 'Unknown'); ?></h2>
                            <?php if ($showRetentionPrompt): ?>
                                <div class="alert alert-success mt-3" role="alert">
                                    Ready to level up? Book your next session now!
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-12">
                            <div class="content">
                                <div class="row">
                                    <!-- Left Column -->
                                    <div class="col-lg-6">
                                        <div class="left-info">
                                            <h4>My Profile</h4><br>
                                            <img src="assets/pictures/<?php echo htmlspecialchars($profile['profile_picture'] ?? ''); ?>" alt="" width="50%"><br><br>
                                            <p><strong>User Name:</strong> <?php echo htmlspecialchars($profile['username'] ?? ''); ?></p>
                                            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($profile['fullname'] ?? ''); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email'] ?? ''); ?></p>
                                            <p><strong>Bio:</strong> <?php echo htmlspecialchars($profile['bio'] ?? ''); ?></p>
                                            <p><strong>Experience:</strong> <?php echo htmlspecialchars($profile['experience_years'] ?? ''); ?> years</p>
                                            <p><strong>Coaching Goal:</strong> <?php echo htmlspecialchars($profile['coaching_goal'] ?? ''); ?></p>
                                            <p><strong>Rating:</strong> <?php echo htmlspecialchars($profile['rating'] ?? '0'); ?>/5</p>
                                            <br><br>
                                            <hr>
                                            <br><br>
                                            <h5>Games</h5><br>
                                            <?php if ($gamesResult && $gamesResult->num_rows > 0): ?>
                                                <?php while ($game = $gamesResult->fetch_assoc()): ?>
                                                    <p><?php echo htmlspecialchars($game['name']); ?> (Rank: <?php echo htmlspecialchars($game['rank_name'] ?? 'N/A'); ?>, Role: <?php echo htmlspecialchars($game['role_name'] ?? 'N/A'); ?>)</p>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p>No games linked yet.</p>
                                            <?php endif; ?>
                                            <br><br>
                                            <hr>
                                            <br><br>
                                            <h5>Recent 3 Feedbacks</h5><br>
                                            <?php if ($feedbackResult && $feedbackResult->num_rows > 0): ?>
                                                <?php while ($fb = $feedbackResult->fetch_assoc()): ?>
                                                    <p><strong><?php echo htmlspecialchars($fb['rating']); ?></strong> (<?php echo htmlspecialchars($fb['numeric_rating']); ?>/5) - <?php echo htmlspecialchars($fb['comments']); ?> <em><?php echo date("M d, Y", strtotime($fb['created_at'])); ?></em></p>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p>No feedback yet.</p>
                                            <?php endif; ?>

                                        </div>
                                    </div>

                                    <!-- Right Column -->

                                    <div class="col-lg-6">
                                        <div class="right-info">
                                            <h4>Control Hub</h4>
                                            <?php include 'p_menu.php'; ?>
                                        </div>

                                       <div class="right-info mt-4">
    <h4>My Challenges & Rewards</h4><br>

    <?php if ($challengeData && $challengeData->num_rows > 0): ?>
        <?php 
        $badges = [];
        $progressBlocks = [];
        $startBlocks = [];
        $allCompleted = true;

        while ($ch = $challengeData->fetch_assoc()):
            // Completed badge
            if ($ch['challenge_status'] === 'Completed' && $ch['reward_status'] === 'Active' && !empty($ch['badge_image_path'])) {
                $badges[] = '<img src="'.htmlspecialchars($ch['badge_image_path']).'" alt="Badge" style="height:120px;width:120px;margin:5px;">';
            }

            // Active progress
            if ($ch['challenge_status'] !== 'Completed' && $ch['reward_status'] === 'Active') {
                $progressBlocks[] = "<hr><h5>Challenge Progress</h5><p><strong>".htmlspecialchars($ch['name']).":</strong> Progress ".(int)$ch['progress_value']."/".(int)$ch['target_value']."</p>";
                $allCompleted = false;
            }

            // Expired challenge
            if ($ch['reward_status'] === 'Expired' && $ch['status'] !== 'Permanent') {
                $startBlocks[] = "<hr><h5>".htmlspecialchars($ch['name'])." (Expired)</h5>
                <form method='post'>
                    <input type='hidden' name='start_challenge_id' value='".(int)$ch['challenge_id']."'>
                    <button type='submit' class='main-button'>Restart Challenge</button>
                </form>";
                $allCompleted = false;
            }

            // Not started challenge
            if (empty($ch['challenge_status']) && $ch['status'] !== 'Permanent') {
                $startBlocks[] = "<hr><h5>".htmlspecialchars($ch['name'])."</h5>
                <form method='post'>
                    <input type='hidden' name='start_challenge_id' value='".(int)$ch['challenge_id']."'>
                    <button type='submit' class='main-button'>Start Challenge</button>
                </form>";
                $allCompleted = false;
            }
        endwhile;
        ?>

        <!-- Show all badges together -->
        <?php if (!empty($badges)): ?>
            <div class="badge-block">
                <?php echo implode(" ", $badges); ?>
            </div>
            <br><hr>
        <?php endif; ?>

        <!-- Show progress blocks -->
        <?php if (!empty($progressBlocks)): ?>
            <?php echo implode(" ", $progressBlocks); ?>
        <?php endif; ?>

        <!-- Show start/restart blocks -->
        <?php if (!empty($startBlocks)): ?>
            <?php echo implode(" ", $startBlocks); ?>
        <?php endif; ?>

        <?php if ($allCompleted): ?>
            <hr>
            <p>You have completed all the challenges.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>No challenges available.</p>
    <?php endif; ?>
</div>



                                        <div class="right-info mt-4">
                                            <h4>Player Stats</h4>
                                            <canvas id="ratingLineChart"></canvas>
                                            <canvas id="feedbackPieChart" class="mt-4"></canvas>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Custom colors (same as admin)
    const chartColors = ["#7F00F2", "#9a00e6", "#5800f6", "#4400d3", "#5305fb"];

    // Line Chart: Rating Trend
    new Chart(document.getElementById("ratingLineChart"), {
        type: "line",
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: "Rating Trend",
                data: <?php echo json_encode($ratings); ?>,
                borderColor: chartColors[1],
                backgroundColor: "rgba(154,0,230,0.2)",
                tension: 0.3,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: chartColors[1]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: "#ffffff"
                    }
                },
                title: {
                    display: true,
                    text: "Player Rating Trend",
                    color: "#ffffff"
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: "#ffffff"
                    },
                    grid: {
                        color: "rgba(255,255,255,0.2)"
                    }
                },
                y: {
                    min: 0,
                    max: 5,
                    ticks: {
                        stepSize: 1,
                        color: "#ffffff"
                    },
                    grid: {
                        color: "rgba(255,255,255,0.2)"
                    }
                }
            }
        }
    });

    // Pie Chart: Feedback Distribution
    new Chart(document.getElementById("feedbackPieChart"), {
        type: "pie",
        data: {
            labels: <?php echo json_encode($categories); ?>,
            datasets: [{
                label: "Feedbacks",
                data: <?php echo json_encode($counts); ?>,
                backgroundColor: chartColors
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: "#ffffff"
                    }
                },
                title: {
                    display: true,
                    text: "Feedback Distribution",
                    color: "#ffffff"
                }
            }
        }
    });
</script>


<?php
include 'footer.php';
?>