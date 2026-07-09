<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Player");
require_once "db_connect.php";

$quizId = intval($_GET['quiz_id']);
$quiz = $conn->query("SELECT * FROM quizzes WHERE quiz_id=$quizId")->fetch_assoc();
$questions = $conn->query("SELECT * FROM quiz_questions WHERE quiz_id=$quizId");

// Get player profile_id
$username = $_SESSION['username'];
$profileRes = $conn->query("SELECT profile_id FROM profiles WHERE username='$username'");
$profile = $profileRes->fetch_assoc();
$profileId = $profile['profile_id'];

$error = "";
$success = "";

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = 0;
    $total = $questions->num_rows;
    $questions->data_seek(0);

    while ($q = $questions->fetch_assoc()) {
        $qid = $q['question_id'];
        $answer = $_POST['answer_' . $qid] ?? null;
        if ($answer === $q['correct_option']) {
            $score++;
        }
    }

    $percentage = ($total > 0) ? round(($score / $total) * 100, 2) : 0;

    // Save attempt
    $conn->query("INSERT INTO quiz_attempts (quiz_id, player_profile_id, score) 
                  VALUES ($quizId, $profileId, $percentage)");

    // Update challenges
    $activeChallenges = $conn->query("
        SELECT pcr.id, c.name, c.target_value, c.challenge_id
        FROM player_challenges_reward pcr
        JOIN challenges c ON pcr.challenge_id = c.challenge_id
        WHERE pcr.player_profile_id=$profileId AND pcr.reward_status='Active' AND pcr.challenge_status='Pending'
    ");

    while ($ch = $activeChallenges->fetch_assoc()) {
        $update = false;
        if (strpos($ch['name'], 'Take') !== false) {
            // Take X quizzes challenge
            $conn->query("UPDATE player_challenges_reward 
                          SET progress_value = progress_value + 1 
                          WHERE id=" . $ch['id']);
            $update = true;
        } elseif (strpos($ch['name'], 'Score 70%') !== false && $percentage >= 70) {
            // Score ≥70% challenge
            $conn->query("UPDATE player_challenges_reward 
                          SET progress_value = progress_value + 1 
                          WHERE id=" . $ch['id']);
            $update = true;
        }

        if ($update) {
            // Check completion
            $check = $conn->query("SELECT progress_value FROM player_challenges_reward WHERE id=" . $ch['id'])->fetch_assoc();
            if ($check['progress_value'] >= $ch['target_value']) {
                $conn->query("UPDATE player_challenges_reward 
                              SET challenge_status='Completed', date_completed=NOW() 
                              WHERE id=" . $ch['id']);
            }
        }
    }

    $success = "Quiz submitted successfully. Your score: $percentage%";
}

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
                                <h6><em>Attempt the quiz below</em></h6>
                                <h4>Take Quiz</h4>
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
                                            <div class="escp_form">
                                                <div class="container my-5">
                                                    <div class="row justify-content-center">
                                                        <div class="col-lg-12">
                                                            <h4><?php echo htmlspecialchars($quiz['title']); ?></h4>
                                                            <p><?php echo htmlspecialchars($quiz['description']); ?></p><br>

                                                            <?php if (!empty($error)): ?>
                                                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                                            <?php elseif (!empty($success)): ?>
                                                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                                            <?php endif; ?>

                                                            <form method="post">
                                                                <?php $questions->data_seek(0); ?>
                                                                <?php while ($q = $questions->fetch_assoc()): ?>
                                                                    <div class="question-block">
                                                                        <p><strong><?php echo htmlspecialchars($q['question_text']); ?></strong></p>
                                                                        <label><input type="radio" name="answer_<?php echo $q['question_id']; ?>" value="A"> <?php echo htmlspecialchars($q['option_a']); ?></label><br>
                                                                        <label><input type="radio" name="answer_<?php echo $q['question_id']; ?>" value="B"> <?php echo htmlspecialchars($q['option_b']); ?></label><br>
                                                                        <label><input type="radio" name="answer_<?php echo $q['question_id']; ?>" value="C"> <?php echo htmlspecialchars($q['option_c']); ?></label><br>
                                                                        <label><input type="radio" name="answer_<?php echo $q['question_id']; ?>" value="D"> <?php echo htmlspecialchars($q['option_d']); ?></label><br>
                                                                        <hr>
                                                                    </div>
                                                                <?php endwhile; ?>
                                                                <button type="submit" class="main-button">Submit Quiz</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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