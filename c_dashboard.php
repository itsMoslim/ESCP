<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Coach");
include 'db_connect.php';

$profileId = $_SESSION['profile_id'];

// Fetch coach profile info
$profileResult = $conn->query("SELECT p.*, c.fullname, c.email, c.hourly_rate
                               FROM profiles p
                               LEFT JOIN coaches c ON p.username = c.username
                               WHERE p.profile_id = $profileId
                               AND p.profile_type = 'Coach'");
$profile = $profileResult ? $profileResult->fetch_assoc() : null;

// Fetch coaching formats
$formatsResult = $conn->query("SELECT g.name, cf.format, cf.coaching_goal
                               FROM coach_formats cf
                               JOIN games g ON cf.game_id = g.game_id
                               WHERE cf.profile_id = $profileId");

// Fetch solo sessions (recent 3)
$soloResult = $conn->query("SELECT s.session_date, s.start_time, s.end_time, g.name, s.status
                            FROM solo_sessions s
                            JOIN games g ON s.game_id = g.game_id
                            WHERE s.coach_profile_id = $profileId
                            ORDER BY s.session_date DESC
                            LIMIT 3");

// Fetch group sessions (recent 3)
$groupResult = $conn->query("SELECT gs.session_date, gs.start_time, gs.end_time, g.name, gs.status
                             FROM group_sessions gs
                             JOIN games g ON gs.game_id = g.game_id
                             WHERE gs.coach_profile_id = $profileId
                             ORDER BY gs.session_date DESC
                             LIMIT 3");

// Fetch top 3 reviews
$reviewsResult = $conn->query("SELECT rating, comments, created_at
                               FROM reviews
                               WHERE coach_profile_id = $profileId
                               ORDER BY created_at DESC
                               LIMIT 3");

// Ratings for line chart (last 10 reviews)
$ratings = [];
$dates = [];
$ratingResult = $conn->query("SELECT rating, created_at
                              FROM reviews
                              WHERE coach_profile_id = $profileId
                              ORDER BY created_at DESC
                              LIMIT 10");
if ($ratingResult) {
    while ($row = $ratingResult->fetch_assoc()) {
        $ratings[] = $row['rating'];
        $dates[] = date("M d", strtotime($row['created_at']));
    }
}

// Feedback distribution for pie chart
$categories = [];
$counts = [];
$catResult = $conn->query("SELECT rating, COUNT(*) as count
                           FROM reviews
                           WHERE coach_profile_id = $profileId
                           GROUP BY rating");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row['rating'];
        $counts[] = $row['count'];
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
                                <h6><em>Coach overview and performance insights.</em></h6>
                                <h4>COACH DASHBOARD</h4>
                                <div class="line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="game-details">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2>Welcome <?php echo htmlspecialchars($profile['username'] ?? 'Unknown'); ?></h2>
                        </div>
                        <div class="col-lg-12">
                            <div class="content">
                                <div class="row">
                                    <!-- Left Column -->
                                    <div class="col-lg-6">
                                        <div class="left-info">
                                            <h4>My Profile</h4><br>
                                            <img src="assets/pictures/<?php echo htmlspecialchars($profile['profile_picture'] ?? ''); ?>" /><br><br>
                                            <p><strong>User Name:</strong> <?php echo htmlspecialchars($profile['username']); ?></p>
                                            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($profile['fullname']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                                            <p><strong>Bio:</strong> <?php echo htmlspecialchars($profile['bio']); ?></p>
                                            <p><strong>Experience:</strong> <?php echo htmlspecialchars($profile['experience_years']); ?> years</p>
                                            <p><strong>Hourly Rate:</strong> <?php echo htmlspecialchars($profile['hourly_rate']); ?> SAR</p>
                                            <p><strong>Rating:</strong> <?php echo htmlspecialchars($profile['rating']); ?>/5</p>
                                          <br><br>
                                            <hr>
<br><br>

                                            <h5>Coaching Formats</h5><br>
                                            <?php if ($formatsResult && $formatsResult->num_rows > 0): ?>
                                                <?php while ($fmt = $formatsResult->fetch_assoc()): ?>
                                                    <p><?php echo htmlspecialchars($fmt['name']); ?> - <?php echo htmlspecialchars($fmt['format']); ?> (Goal: <?php echo htmlspecialchars($fmt['coaching_goal']); ?>)</p>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p>No formats defined yet.</p>
                                            <?php endif; ?>
                                          <br><br>
                                            <hr>
<br><br>

                                            <h5>Recent 3 Reviews</h5><br>
                                            <?php if ($reviewsResult && $reviewsResult->num_rows > 0): ?>
                                                <?php while ($rev = $reviewsResult->fetch_assoc()): ?>
                                                    <p><strong>Rating:</strong> <?php echo htmlspecialchars($rev['rating']); ?>/5 - <?php echo htmlspecialchars($rev['comments']); ?> <em><?php echo date("M d, Y", strtotime($rev['created_at'])); ?></em></p>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p>No reviews yet.</p>
                                            <?php endif; ?>
<br><br>
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="col-lg-6">
                                        <div class="right-info">
                                            <h4>Control Hub</h4><br>
                                            <?php include 'c_menu.php'; ?>
                                        </div>
                                        <div class="right-info mt-4">
                                            <h4>Coach Stats</h4>
                                            <canvas id="ratingLineChart"></canvas>
                                            <canvas id="feedbackPieChart" class="mt-4"></canvas>
                                            <canvas id="sessionsBarChart" class="mt-4"></canvas>
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
    // Custom colors
    const chartColors = ["#7F00F2", "#9a00e6", "#5800f6", "#4400d3", "#5305fb"];

    // Line Chart: Ratings Trend
    new Chart(document.getElementById("ratingLineChart"), {
        type: "line",
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: "Ratings Trend",
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
            responsive: true
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

    // Bar Chart: Sessions Completed vs Cancelled
    new Chart(document.getElementById("sessionsBarChart"), {
        type: "bar",
        data: {
            labels: ["Completed", "Cancelled"],
            datasets: [{
                label: "Sessions",
                data: [
                    <?php
                    // Count completed and cancelled sessions
                    $completedCount = 0;
                    $cancelledCount = 0;
                    $statusResult = $conn->query("SELECT status, COUNT(*) as cnt
                                              FROM solo_sessions
                                              WHERE coach_profile_id = $profileId
                                              GROUP BY status");
                    if ($statusResult) {
                        while ($row = $statusResult->fetch_assoc()) {
                            if ($row['status'] === 'Completed') $completedCount += $row['cnt'];
                            if ($row['status'] === 'Cancelled') $cancelledCount += $row['cnt'];
                        }
                    }
                    $statusResult2 = $conn->query("SELECT status, COUNT(*) as cnt
                                               FROM group_sessions
                                               WHERE coach_profile_id = $profileId
                                               GROUP BY status");
                    if ($statusResult2) {
                        while ($row = $statusResult2->fetch_assoc()) {
                            if ($row['status'] === 'Completed') $completedCount += $row['cnt'];
                            if ($row['status'] === 'Cancelled') $cancelledCount += $row['cnt'];
                        }
                    }
                    echo $completedCount . "," . $cancelledCount;
                    ?>
                ],
                backgroundColor: [chartColors[0], chartColors[2]]
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
                    text: "Sessions Status Overview",
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
                    ticks: {
                        color: "#ffffff"
                    },
                    grid: {
                        color: "rgba(255,255,255,0.2)"
                    }
                }
            }
        }
    });
</script>

<?php
include 'footer.php';
?>