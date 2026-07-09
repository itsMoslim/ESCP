<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Player");
require_once "db_connect.php";

// Fetch games for dropdown
$games = $conn->query("SELECT game_id, name FROM games ORDER BY name");

// Handle search filter
$selectedGame = isset($_GET['game_id']) ? intval($_GET['game_id']) : null;
$quizQuery = "SELECT q.quiz_id, q.title, q.description, g.name AS game_name 
              FROM quizzes q 
              JOIN games g ON q.game_id = g.game_id";
if ($selectedGame) {
    $quizQuery .= " WHERE q.game_id = $selectedGame";
}
$quizzes = $conn->query($quizQuery);

// Fetch quiz history for player
$username = $_SESSION['username'];
$profileRes = $conn->query("SELECT profile_id FROM profiles WHERE username='$username'");
$profile = $profileRes->fetch_assoc();
$profileId = $profile['profile_id'];

$history = $conn->query("
    SELECT qa.score, qa.attempt_date, q.title 
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.quiz_id
    WHERE qa.player_profile_id = $profileId
    ORDER BY qa.attempt_date DESC
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
                                <h6><em>Browse and attempt quizzes</em></h6>
                                <h4>Quest Hub</h4>
                                <div class="line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="game-details">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2>Welcome <?php echo htmlspecialchars($_SESSION['role'] ?? 'Player'); ?></h2>
                        </div>
                        <div class="col-lg-12">
                            <div class="content">
                                <div class="row">
                                    <!-- Left Column -->
                                    <div class="col-lg-6">
                                        <div class="left-info">
                                            <h4>Available Quizzes</h4><br>

                                            <form method="get" class="mb-3">
                                                <label class="form-label">Filter by Game</label>
                                                <select name="game_id" class="form-control" onchange="this.form.submit()">
                                                    <option value="">-- All Games --</option>
                                                    <?php while ($g = $games->fetch_assoc()): ?>
                                                        <option value="<?php echo $g['game_id']; ?>" <?php if ($selectedGame == $g['game_id']) echo 'selected'; ?>>
                                                            <?php echo htmlspecialchars($g['name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </form><br><br>

                                            <?php if ($quizzes && $quizzes->num_rows > 0): ?>
                                                <?php while ($q = $quizzes->fetch_assoc()): ?>
                                                    <div class="quiz-block">
                                                        <h5><?php echo htmlspecialchars($q['title']); ?></h5>
                                                        <p><?php echo htmlspecialchars($q['description']); ?></p>
                                                        <p><small>Game: <?php echo htmlspecialchars($q['game_name']); ?></small></p>
                                                        <a href="p_takequiz.php?quiz_id=<?php echo $q['quiz_id']; ?>" class="main-button">Take Quiz</a>
                                                        <hr>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p>No quizzes available for this game.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="col-lg-6">
                                         <div class="right-info">
                                            <h4>Control Hub</h4>
                                            <?php include 'p_menu.php'; ?>
                                        </div>
                                        <div class="right-info">
                                            <h4>Quiz History</h4><br>
                                            <?php if ($history && $history->num_rows > 0): ?>
                                                <p>
                                                    <?php while ($h = $history->fetch_assoc()): ?>
                                                        <br>
                                                            <strong><?php echo htmlspecialchars($h['title']); ?></strong> <br>
                                                            Score: <?php echo $h['score']; ?>% 
                                                            (<?php echo date("M d, Y H:i", strtotime($h['attempt_date'])); ?>)
                                                        <br>
                                                    <?php endwhile; ?>
                                                    </p>
                                            <?php else: ?>
                                                <p>No quiz attempts yet.</p>
                                            <?php endif; ?>
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
