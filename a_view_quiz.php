<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Admin");
require_once 'db_connect.php';

$error = "";

$quizId = intval($_GET['id'] ?? 0);
if ($quizId <= 0) {
    $error = "Invalid quiz ID.";
} else {
    // Fetch quiz details
    $stmt = $conn->prepare("
        SELECT q.title, q.description, q.created_at, g.name AS game_name
        FROM quizzes q
        JOIN games g ON q.game_id = g.game_id
        WHERE q.quiz_id = ?
    ");
    $stmt->bind_param("i", $quizId);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();

    if (!$quiz) {
        $error = "Quiz not found.";
    } else {
      
        $stmt = $conn->prepare("
            SELECT question_text, option_a, option_b, option_c, option_d, correct_option
            FROM quiz_questions
            WHERE quiz_id = ?
            ORDER BY question_id ASC
        ");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $questions = $stmt->get_result();
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
                                <h6><em>Review quiz details and all questions.</em></h6>
                                <h4>View Quiz</h4>
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
                                    <!-- Left Column: Quiz Details -->
                                    <div class="col-lg-8">
                                        <div class="left-info">
                                            <h4>Quiz Details</h4><br><br><br>

                                            <?php if (!empty($error)): ?>
                                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                            <?php else: ?>
                                                <h5><?php echo htmlspecialchars($quiz['title']); ?></h5>
                                                <h6>Category: <?php echo htmlspecialchars($quiz['game_name']); ?></h6>
                                                <?php if (!empty($quiz['description'])): ?>
                                                    <p><?php echo nl2br(htmlspecialchars($quiz['description'])); ?></p>
                                                <?php endif; ?>
                                                <p><small>Created At: <?php echo $quiz['created_at']; ?></small></p>
                                                <hr>

                                                <h4>Questions</h4><br><br>
                                                <?php 
                                                if ($questions && $questions->num_rows > 0) {
                                                    $i = 1;
                                                    while ($q = $questions->fetch_assoc()) {
                                                        echo "<h5>Question $i</h5>";
                                                        echo "<p>" . nl2br(htmlspecialchars($q['question_text'])) . "</p>";
                                                        echo "<p>A) " . htmlspecialchars($q['option_a']) . "</p>";
                                                        echo "<p>B) " . htmlspecialchars($q['option_b']) . "</p>";
                                                        echo "<p>C) " . htmlspecialchars($q['option_c']) . "</p>";
                                                        echo "<p>D) " . htmlspecialchars($q['option_d']) . "</p>";
                                                        echo "<p><strong>Correct Answer: " . htmlspecialchars($q['correct_option']) . "</strong></p>";
                                                        echo "<hr>";
                                                        $i++;
                                                    }
                                                } else {
                                                    echo "<p>No questions found for this quiz.</p>";
                                                }
                                                ?>
                                            <?php endif; ?>

                                            <p><a href="a_quiz_management.php" class="main-button">Back to Quizzes</a></p>
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
