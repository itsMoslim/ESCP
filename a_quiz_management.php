<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Admin");
require_once 'db_connect.php';

// Fetch quizzes with game category
$result = $conn->query("
    SELECT q.quiz_id, q.title, q.description, g.name AS game_name, q.created_at
    FROM quizzes q
    JOIN games g ON q.game_id = g.game_id
    ORDER BY q.created_at DESC
");
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
                                <h6><em>Manage quizzes and review their details.</em></h6>
                                <h4>Quiz Management</h4>
                                <div class="line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="game-details">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2>Welcome <?php echo htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?></h2>
                        </div>
                        <div class="col-lg-12">
                            <div class="content">
                                <div class="row">
                                    <!-- Left Column: Quizzes -->
                                    <div class="col-lg-8">
                                        <div class="left-info">
                                            <div class="left">
                                                <h4>All Quizzes</h4>
                                            </div>
                                            <br><br><br>

                                            <!-- Create New Quiz Button -->
                                            <div class="mb-3">
                                                <a href="a_create_quiz.php" class="main-button">
                                                    <i class="fa fa-plus"></i> Create New Quiz
                                                </a>
                                            </div>
                                            <br><br>
                                            <!-- Quiz List -->
                                            <?php
                                            if ($result && $result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo "<h5>" . htmlspecialchars($row['title']) . "</h5>";
                                                    echo "<h6>Category: " . htmlspecialchars($row['game_name']) . "</h6>";
                                                    if (!empty($row['description'])) {
                                                        echo "<p>" . nl2br(htmlspecialchars($row['description'])) . "</p>";
                                                    }
                                                    echo "<p><small>Created At: " . $row['created_at'] . "</small></p>";
                                                    echo '<p><a href="a_view_quiz.php?id=' . (int)$row['quiz_id'] . '" class="main-button">View Quiz</a></p>';
                                                    echo "<hr>";
                                                }
                                            } else {
                                                echo "<p>No quizzes found.</p>";
                                            }
                                            ?>
                                        </div>
                                    </div>

                                    <!-- Right Column: Control Hub -->
                                    <div class="col-lg-4">
                                        <div class="right-info">
                                            <h4>Control Hub</h4>
                                            <?php include 'a_menu.php'; ?>
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