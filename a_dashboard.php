<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Admin");
require_once 'db_connect.php';

// Summary counts
$playersCount     = $conn->query("SELECT COUNT(*) AS cnt FROM players")->fetch_assoc()['cnt'];
$coachesCount     = $conn->query("SELECT COUNT(*) AS cnt FROM coaches")->fetch_assoc()['cnt'];
$sessionsCount    = $conn->query("SELECT COUNT(*) AS cnt FROM solo_sessions")->fetch_assoc()['cnt']
    + $conn->query("SELECT COUNT(*) AS cnt FROM group_sessions")->fetch_assoc()['cnt'];
$paymentsCount    = $conn->query("SELECT COUNT(*) AS cnt FROM payments")->fetch_assoc()['cnt'];
$reviewsCount     = $conn->query("SELECT COUNT(*) AS cnt FROM reviews")->fetch_assoc()['cnt'];
$rewardsCount     = $conn->query("SELECT COUNT(*) AS cnt FROM rewards")->fetch_assoc()['cnt'];
$challengesCount  = $conn->query("SELECT COUNT(*) AS cnt FROM challenges")->fetch_assoc()['cnt'];

// Subscriptions by category (Solo vs Group sessions)
$subscriptionsByCategory = [];
$res = $conn->query("
    SELECT session_type AS category, COUNT(*) AS cnt
    FROM payment_sessions
    GROUP BY session_type
");
while ($row = $res->fetch_assoc()) {
    $subscriptionsByCategory[$row['category']] = (int)$row['cnt'];
}

// Monthly revenue trend
$sessionsOverTime = [];
$res = $conn->query("
    SELECT DATE_FORMAT(paid_at, '%b %Y') AS month, SUM(amount) AS total
    FROM payments
    WHERE status = 'Paid' AND paid_at IS NOT NULL
    GROUP BY month
    ORDER BY MIN(paid_at)
");
while ($row = $res->fetch_assoc()) {
    $sessionsOverTime[$row['month']] = (float)$row['total'];
}
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0"></script>
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <div class="page-content">
                <!-- Banner -->
                <div class="main-banner">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="header-text">
                                <h6><em>Administrator overview and insights.</em></h6>
                                <h4>DASHBOARD</h4>
                                <div class="line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="game-details">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2>Welcome <?php echo htmlspecialchars($_SESSION['role'] ?? 'Unknown'); ?></h2>
                        </div>
                        <div class="col-lg-12">
                            <div class="content">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="left-info">
                                            <div class="left">
                                                <h4>Platform Overview</h4>
                                                <span>contains all important <br>summary metrics </span>
                                            </div>
                                            <ul>
                                                <li>Players: <?php echo (int)$playersCount; ?></li>
                                                <li>Coaches: <?php echo (int)$coachesCount; ?></li>
                                                <li>Sessions: <?php echo (int)$sessionsCount; ?></li>
                                                <li>Payments: <?php echo (int)$paymentsCount; ?></li>
                                                <li>Reviews: <?php echo (int)$reviewsCount; ?></li>
                                                <li>Rewards: <?php echo (int)$rewardsCount; ?></li>
                                                <li>Challenges: <?php echo (int)$challengesCount; ?></li>
                                            </ul>

                                            <!-- Chart for summary metrics -->
                                            <canvas id="overviewChart" style="max-width:500px; margin-top:20px;"></canvas>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="right-info">
                                            <h4>Control Hub</h4>
                                            <?php include 'a_menu.php'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="left-info">
                                            <div class="left">
                                                <h4>Session Distribution</h4>
                                            </div>
                                            <!-- Chart for subscription summary -->
                                            <canvas id="SubscriptionsChart"></canvas>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="right-info">
                                            <h4>Revenue Trend</h4>
                                            <!-- Chart for revenue summary -->
                                            <canvas id="SessionBookingChart"></canvas>
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

<!-- Chart.js -->
<script>
    // Custom colors
    const chartColors = ["#7F00F2", "#9a00e6", "#5800f6", "#4400d3", "#5305fb"];

    // Bar chart: summary metrics
    new Chart(document.getElementById("overviewChart"), {
        type: "bar",
        data: {
            labels: ["Players", "Coaches", "Sessions", "Payments", "Reviews", "Rewards", "Challenges"],
            datasets: [{
                label: "Counts",
                data: [
                    <?php echo (int)$playersCount; ?>,
                    <?php echo (int)$coachesCount; ?>,
                    <?php echo (int)$sessionsCount; ?>,
                    <?php echo (int)$paymentsCount; ?>,
                    <?php echo (int)$reviewsCount; ?>,
                    <?php echo (int)$rewardsCount; ?>,
                    <?php echo (int)$challengesCount; ?>
                ],
                backgroundColor: chartColors
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: "#ffffff" } },
                title: { display: true, text: 'Platform Overview Metrics', color: "#ffffff" }
            },
            scales: {
                x: { ticks: { color: "#ffffff" }, grid: { color: "rgba(255,255,255,0.2)" } },
                y: { ticks: { color: "#ffffff" }, grid: { color: "rgba(255,255,255,0.2)" } }
            }
        }
    });

    // Pie chart: Session distribution (Solo vs Group)
    new Chart(document.getElementById("SubscriptionsChart"), {
        type: "pie",
        data: {
            labels: <?php echo json_encode(array_keys($subscriptionsByCategory)); ?>,
            datasets: [{
                label: "Sessions",
                data: <?php echo json_encode(array_values($subscriptionsByCategory)); ?>,
                backgroundColor: chartColors
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: "#ffffff" } },
                title: { display: true, text: 'Sessions by Type', color: "#ffffff" }
            }
        }
    });

    // Line chart: Monthly revenue trend
    new Chart(document.getElementById("SessionBookingChart"), {
        type: "line",
        data: {
            labels: <?php echo json_encode(array_keys($sessionsOverTime)); ?>,
            datasets: [{
                label: "Revenue",
                data: <?php echo json_encode(array_values($sessionsOverTime)); ?>,
                borderColor: chartColors[2],
                backgroundColor: chartColors[2],
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: "#ffffff" } },
                title: { display: true, text: 'Monthly Revenue Trend', color: "#ffffff" }
            },
            scales: {
                x: { ticks: { color: "#ffffff" }, grid: { color: "rgba(255,255,255,0.2)" } },
                y: { ticks: { color: "#ffffff" }, grid: { color: "rgba(255,255,255,0.2)" } }
            }
        }
    });
</script>

<?php include 'footer.php'; ?>
