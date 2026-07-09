<?php
include 'header.php';
require_once 'db_connect.php';

// --- Top 5 Coaches Query ---
$topCoaches = $conn->query("
    SELECT p.profile_id, p.username, c.fullname, p.experience_years, p.rating, p.profile_picture,
           g.name AS game,
           (COALESCE(gs.session_count,0) + COALESCE(ss.session_count,0)) AS total_sessions
    FROM profiles p
    LEFT JOIN coaches c ON p.username = c.username
    LEFT JOIN user_games ug ON p.profile_id = ug.profile_id
    LEFT JOIN games g ON ug.game_id = g.game_id
    LEFT JOIN (
        SELECT coach_profile_id, COUNT(*) AS session_count
        FROM group_sessions GROUP BY coach_profile_id
    ) gs ON p.profile_id = gs.coach_profile_id
    LEFT JOIN (
        SELECT coach_profile_id, COUNT(*) AS session_count
        FROM solo_sessions GROUP BY coach_profile_id
    ) ss ON p.profile_id = ss.coach_profile_id
    WHERE p.profile_type = 'Coach'
    ORDER BY p.rating DESC
    LIMIT 5
");

// --- Leaderboard Query (Star Player last 3 months) ---
$starPlayer = $conn->query("
    SELECT p.profile_id,
           pl.fullname,
           p.bio,
           p.profile_picture,
           g.name AS game_name,
           gr.rank_name,
           gr2.role_name
    FROM player_challenges_reward pcr
    JOIN profiles p ON pcr.player_profile_id = p.profile_id
    JOIN players pl ON p.username = pl.username
    LEFT JOIN user_games ug ON p.profile_id = ug.profile_id
    LEFT JOIN games g ON ug.game_id = g.game_id
    LEFT JOIN game_ranks gr ON ug.rank_id = gr.rank_id
    LEFT JOIN game_roles gr2 ON ug.role_id = gr2.role_id
    WHERE p.profile_type = 'Player'
      AND pcr.challenge_status = 'Completed'
      AND pcr.reward_status = 'Active'
      AND pcr.date_completed >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
    GROUP BY p.profile_id, pl.fullname, p.bio, p.profile_picture, g.name, gr.rank_name, gr2.role_name
    ORDER BY COUNT(pcr.id) DESC
    LIMIT 1
")->fetch_assoc();



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
                                <h6><em>Connect with pro coaches, track progress, and unlock rewards.</em></h6>
                                <h4><em>Level Up </em> Your Game with Expert Coaching</h4>
                                <div class="main-button">
                                    <a href="login.php">Get Started</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empowering Gamers -->
                <div class="escp_content1">
                    <div class="heading-section">
                        <h4><em>Empowering Gamers</em></h4>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-lg-6 text-center">
                            <img src="assets/images/about.png" alt="About ESCP"><br><br>
                        </div>
                        <div class="col-lg-6">
                            <div class="item">
                                <h5><strong>Level Up Your Game</strong></h5>
                                <p>ESCP revolutionizes eSports coaching, giving every player a path to growth and success.</p>
                                <div class="line"></div>
                            </div>
                        </div>
                    </div>
                    <div class="main-button">
                        <a href="feature.php">Features</a>
                    </div>
                </div>

                <!-- Star Player Section -->
                <div class="escp_content1">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="heading-section">
                                <h4><em>Leaderboard</em></h4><br>
                                <h5>Star Player (Last 3 Months)</h5><br>
                            </div>
                            <div class="row align-items-center">
                                <?php if ($starPlayer): ?>
                                    <!-- Profile Picture -->
                                    <div class="col-lg-6 text-center">
                                        <img src="assets/pictures/<?php echo htmlspecialchars($starPlayer['profile_picture']); ?>"
                                            alt="Star Player Picture"
                                            class="img-fluid rounded shadow-sm"
                                            style="max-height:400px; max-width:300px"><br><br>
                                    </div>

                                    <!-- Player Details -->
                                    <!-- Player Details -->
                                    <div class="col-lg-6">
                                        <h3><strong><?php echo htmlspecialchars($starPlayer['fullname']); ?></strong></h3>
                                        <p><strong>Bio:</strong> <?php echo htmlspecialchars($starPlayer['bio']); ?></p>
                                        <p><strong>Game:</strong> <?php echo htmlspecialchars($starPlayer['game_name']); ?></p>
                                        <p><strong>Rank:</strong> <?php echo htmlspecialchars($starPlayer['rank_name']); ?></p>
                                        <p><strong>Role:</strong> <?php echo htmlspecialchars($starPlayer['role_name']); ?></p>
                                        <br><br>

                                        <!-- Badges -->
                                        <p><strong>Achievements:</strong></p>
                                        <div>
                                            <?php
                                            $badges = $conn->query("
            SELECT r.badge_image_path
            FROM player_challenges_reward pcr
            JOIN rewards r ON pcr.challenge_id = r.challenge_id
            WHERE pcr.player_profile_id = " . (int)$starPlayer['profile_id'] . " 
              AND pcr.reward_status = 'Active'
        ");
                                            while ($badge = $badges->fetch_assoc()):
                                            ?>
                                                <img src="<?php echo htmlspecialchars($badge['badge_image_path']); ?>"
                                                    alt="Badge"
                                                    style="height:120px;width:120px;margin:5px;">
                                            <?php endwhile; ?>
                                        </div>
                                        <div class="line"></div>
                                    </div>

                                <?php else: ?>
                                    <p class="text-center">No star player data available yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Top 5 Coaches -->
                <div class="escp_content1">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="featured-games header-text">
                                <div class="heading-section">
                                    <h4><em>Featured Coaches</em></h4>
                                </div>
                                <div class="owl-features owl-carousel">
                                    <?php while ($coach = $topCoaches->fetch_assoc()): ?>
                                        <div class="item">
                                            <div class="thumb">
                                                <img src="assets/pictures/<?php echo htmlspecialchars($coach['profile_picture']); ?>" alt="" height="300px" width="200px">
                                                <div class="hover-effect">
                                                    <a href="coachprofile.php?id=<?php echo (int)$coach['profile_id']; ?>">
                                                        <h6>View Profile</h6>
                                                    </a>
                                                </div>
                                            </div>
                                            <h4><?php echo htmlspecialchars($coach['fullname']); ?><br>
                                                <span><?php echo htmlspecialchars($coach['game']); ?></span><br>
                                                <span class="coach-rating">
                                                    <?php
                                                    $ratingVal = isset($coach['rating']) ? (float)$coach['rating'] : 0.0;
                                                    $ratingVal = max(0.0, min(5.0, $ratingVal));
                                                    ?>
                                                    <i class="fa fa-star" style="color:#7F00F2;"></i>
                                                    <small><?php echo number_format($ratingVal, 1); ?></small>
                                                </span>
                                            </h4>
                                            <ul>
                                                <li><i class="fa fa-running"></i> <?php echo (int)$coach['experience_years']; ?> Years</li>
                                                <li><i class="fa fa-calendar-check"></i> <?php echo (int)$coach['total_sessions']; ?> Sessions</li>
                                            </ul>
                                        </div>
                                    <?php endwhile; ?>
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