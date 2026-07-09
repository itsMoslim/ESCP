<?php
include 'header.php';
require_once 'db_connect.php';

$coachId = intval($_GET['id'] ?? 0);

// Fetch coach details with game, rank, role, format, goal, sessions
$stmt = $conn->prepare("
    SELECT c.fullname, c.hourly_rate, 
           p.bio, p.experience_years, p.rating, p.profile_picture,
           g.name AS game, rk.rank_name AS rank, rl.role_name AS role,
           cf.format, cf.coaching_goal,
           (COALESCE(gs.session_count,0) + COALESCE(ss.session_count,0)) AS total_sessions
    FROM coaches c
    JOIN profiles p ON c.username = p.username
    LEFT JOIN user_games ug ON p.profile_id = ug.profile_id
    LEFT JOIN games g ON ug.game_id = g.game_id
    LEFT JOIN game_ranks rk ON ug.rank_id = rk.rank_id
    LEFT JOIN game_roles rl ON ug.role_id = rl.role_id
    LEFT JOIN coach_formats cf ON p.profile_id = cf.profile_id
    LEFT JOIN (
        SELECT coach_profile_id, COUNT(*) AS session_count
        FROM group_sessions GROUP BY coach_profile_id
    ) gs ON p.profile_id = gs.coach_profile_id
    LEFT JOIN (
        SELECT coach_profile_id, COUNT(*) AS session_count
        FROM solo_sessions GROUP BY coach_profile_id
    ) ss ON p.profile_id = ss.coach_profile_id
    WHERE p.profile_id = ?
");
$stmt->bind_param("i", $coachId);
$stmt->execute();
$coach = $stmt->get_result()->fetch_assoc();



// Fetch last 3 feedbacks dynamically
$feedbacks = $conn->prepare("
    SELECT rating, comments 
    FROM reviews 
    WHERE coach_profile_id = ? 
    ORDER BY review_id DESC 
");
$feedbacks->bind_param("i", $coachId);
$feedbacks->execute();
$feedbackResult = $feedbacks->get_result();


// Fetch solo sessions for this coach
$soloSessions = $conn->prepare("
    SELECT solo_session_id, session_date, start_time, end_time, hourly_rate, status, player_profile_id
    FROM solo_sessions
    WHERE coach_profile_id = ?
    ORDER BY session_date DESC
");
$soloSessions->bind_param("i", $coachId);
$soloSessions->execute();
$soloResult = $soloSessions->get_result();


// Fetch group sessions for this coach
$groupSessions = $conn->prepare("
    SELECT session_id, training_detail, session_date, start_time, end_time, fee, status
    FROM group_sessions
    WHERE coach_profile_id = ?
    ORDER BY session_date DESC
");
$groupSessions->bind_param("i", $coachId);
$groupSessions->execute();
$groupResult = $groupSessions->get_result();


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
                                <h6><em>Coach Profile</em></h6>
                                <h4><?php echo htmlspecialchars($coach['fullname']); ?></h4>
                                <div class="line"></div>
                                <p>Game: <?php echo htmlspecialchars($coach['game']); ?> |
                                    Rank: <?php echo htmlspecialchars($coach['rank']); ?> |
                                    Role: <?php echo htmlspecialchars($coach['role']); ?></p>
                                <p>Rating: <?php echo htmlspecialchars($coach['rating']); ?>/5</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coach Overview -->
                <div class="escp_content1">
                    <div class="row align-items-center">
                        <div class="col-lg-6 text-center">
                            <img src="assets/pictures/<?php echo htmlspecialchars($coach['profile_picture']); ?>"
                                alt="Coach Picture" class="img-fluid rounded shadow-sm"><br><br>
                        </div>
                        <div class="col-lg-6">
                            <h5><strong>About Coach</strong></h5>
                            <p><?php echo htmlspecialchars($coach['bio']); ?></p>
                            <p>Experience: <?php echo (int)$coach['experience_years']; ?> years</p>
                            <p>Hourly Rate: <?php echo htmlspecialchars($coach['hourly_rate']); ?> SAR</p>
                            <p>Game: <?php echo htmlspecialchars($coach['game']); ?></p>
                            <p>Rank: <?php echo htmlspecialchars($coach['rank']); ?></p>
                            <p>Role: <?php echo htmlspecialchars($coach['role']); ?></p>
                            <p>Coaching Format: <?php echo htmlspecialchars($coach['format']); ?></p>
                            <p>Coaching Goal: <?php echo htmlspecialchars($coach['coaching_goal']); ?></p>
                            <p>Total Sessions: <?php echo (int)$coach['total_sessions']; ?></p>

                            <div class="line"></div>
                        </div>
                    </div>
                </div>



                <!-- Solo Sessions -->
                <div class="escp_content1">
                    <div class="heading-section">
                        <h4><em>Solo Sessions</em></h4>
                    </div>
                    <div class="row">
                        <?php while ($ss = $soloResult->fetch_assoc()): ?>
                            <?php
                            $show = false;
                            // Show only Available sessions publicly
                            if ($ss['status'] === 'Available') $show = true;
                            // Requested sessions visible only to requesting player
                            if (
                                $ss['status'] === 'Requested' && isset($_SESSION['role'], $_SESSION['profile_id'])
                                && $_SESSION['role'] === 'Player' && $_SESSION['profile_id'] == $ss['player_profile_id']
                            ) {
                                $show = true;
                            }
                            ?>
                            <?php if ($show): ?>
                                <div class="col-lg-4">
                                    <div class="item">
                                        <h5><?php echo htmlspecialchars($ss['session_date']); ?>
                                            (<?php echo htmlspecialchars($ss['start_time']); ?> - <?php echo htmlspecialchars($ss['end_time']); ?>)</h5>
                                        <p>Fee: <?php echo htmlspecialchars($ss['hourly_rate']); ?> SAR</p>

                                        <?php if ($ss['status'] === 'Available' && isset($_SESSION['role']) && $_SESSION['role'] === 'Player'): ?>
                                            <div class="main-button">
                                                <a href="book_solo.php?solo_session_id=<?php echo (int)$ss['solo_session_id']; ?>">Book Session</a>
                                            </div>
                                        <?php else: ?>
                                            <p>Status: <?php echo htmlspecialchars($ss['status']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Separate Request Session option -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Player'): ?>
                    <div class="escp_content1">
                        <div class="heading-section">
                            <h4><em>Request a Custom Session</em></h4>
                        </div>
                        <p>If none of the available slots fit your schedule, you can request a session according to your availability.</p>
                        <div class="main-button">
                            <a href="request_solo.php?id=<?php echo $coachId ?>">Request Session</a>
                        </div>
                    </div>
                <?php endif; ?>




                <!-- Group Sessions  -->
                <div class="escp_content1">
                    <div class="heading-section">
                        <h4><em>Group Sessions</em></h4>
                    </div>
                    <div class="row">
                        <?php while ($gs = $groupResult->fetch_assoc()): ?>
                            <?php if ($gs['status'] === 'Scheduled' || $gs['status'] === 'Ongoing'): ?>
                                <div class="col-lg-4">
                                    <div class="item">
                                        <h5><?php echo htmlspecialchars($gs['training_detail']); ?></h5>
                                        <p><?php echo htmlspecialchars($gs['session_date']); ?>
                                            (<?php echo htmlspecialchars($gs['start_time']); ?> - <?php echo htmlspecialchars($gs['end_time']); ?>)</p>
                                        <p>Fee: <?php echo htmlspecialchars($gs['fee']); ?> SAR</p>

                                        <?php if (
                                            $gs['status'] === 'Scheduled'
                                            && isset($_SESSION['role'])
                                            && $_SESSION['role'] === 'Player'
                                        ): ?>
                                            <div class="main-button">
                                                <a href="book_group.php?session_id=<?php echo (int)$gs['session_id']; ?>">
                                                    Book Group Session
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <p>Status: <?php echo htmlspecialchars($gs['status']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>


                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Player'): ?>
                    <!-- Access Control -->
                    <div class="alert alert-warning" style="color:red;">
                        You must be logged in as a player to book a session.
                        <a href="login.php" style="color:#7F00F2">Login</a>
                    </div>
                <?php endif; ?>

                
                <!-- Feedback Section -->
                <div class="escp_content1">
                    <div class="heading-section">
                        <h4><em>Recent Feedback</em></h4>
                    </div>
                    <div class="row">
                        <?php while ($fb = $feedbackResult->fetch_assoc()): ?>
                            <div class="col-lg-4">
                                <div class="item">
                                    <h5><?php echo htmlspecialchars($fb['rating']); ?>/5</h5>
                                    <p><?php echo htmlspecialchars($fb['comments']); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>