<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Admin");
require_once 'db_connect.php';

$error = "";
$success = "";

// Fetch games list for dropdown
$games = $conn->query("SELECT game_id, name FROM games ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $gameId      = intval($_POST['game_id'] ?? 0);

   
    if (empty($title) || $gameId <= 0) {
        $error = "Quiz title and game category are required.";
    } else {
        $conn->begin_transaction();
        try {
         
            $stmt = $conn->prepare("INSERT INTO quizzes (title, description, game_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $title, $description, $gameId);
            $stmt->execute();
            $quizId = $stmt->insert_id;

           
            for ($i = 1; $i <= 5; $i++) {
                $qText   = trim($_POST["question_text_$i"] ?? '');
                $optA    = trim($_POST["option_a_$i"] ?? '');
                $optB    = trim($_POST["option_b_$i"] ?? '');
                $optC    = trim($_POST["option_c_$i"] ?? '');
                $optD    = trim($_POST["option_d_$i"] ?? '');
                $correct = $_POST["correct_option_$i"] ?? '';

                if (empty($qText) || empty($optA) || empty($optB) || empty($optC) || empty($optD) || empty($correct)) {
                    throw new Exception("All fields for question $i are required.");
                }

                $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $quizId, $qText, $optA, $optB, $optC, $optD, $correct);
                $stmt->execute();
            }

            $conn->commit();
            $success = "Quiz with 5 questions created successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to create quiz: " . $e->getMessage();
        }
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
                                <h6><em>Add a new quiz with 5 questions.</em></h6>
                                <h4>Create New Quiz</h4>
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
                                    <!-- Left Column: Quiz Form -->
                                    <div class="col-lg-8">
                                        <div class="left-info">
                                            <h4>Create Quiz</h4><br>

                                            <?php if (!empty($error)): ?>
                                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                            <?php elseif (!empty($success)): ?>
                                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                            <?php endif; ?>
                                            <div class="escp_form">
                                                <div class="container my-5">
                                                    <div class="row justify-content-center">
                                                        <div class="col-lg-12">
                                                            <form method="POST">
                                                                <!-- Quiz Title -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Quiz Title</label>
                                                                    <input type="text" class="form-control" name="title" required>
                                                                </div>

                                                                <!-- Description -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Description</label>
                                                                    <textarea class="form-control" name="description"></textarea>
                                                                </div>

                                                                <!-- Game Category -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Game Category</label>
                                                                    <select class="form-control" name="game_id" required>
                                                                        <option value="">Select a game</option>
                                                                        <?php while ($g = $games->fetch_assoc()): ?>
                                                                            <option value="<?php echo $g['game_id']; ?>">
                                                                                <?php echo htmlspecialchars($g['name']); ?>
                                                                            </option>
                                                                        <?php endwhile; ?>
                                                                    </select>
                                                                </div>

                                                                <hr>
                                                                <h5>Questions</h5><br><br>
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Question <?php echo $i; ?></label>
                                                                        <textarea class="form-control" name="question_text_<?php echo $i; ?>" required></textarea>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Option A</label>
                                                                        <input type="text" class="form-control" name="option_a_<?php echo $i; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Option B</label>
                                                                        <input type="text" class="form-control" name="option_b_<?php echo $i; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Option C</label>
                                                                        <input type="text" class="form-control" name="option_c_<?php echo $i; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Option D</label>
                                                                        <input type="text" class="form-control" name="option_d_<?php echo $i; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Correct Answer</label>
                                                                        <select class="form-control" name="correct_option_<?php echo $i; ?>" required>
                                                                            <option value="">Select correct option</option>
                                                                            <option value="A">A</option>
                                                                            <option value="B">B</option>
                                                                            <option value="C">C</option>
                                                                            <option value="D">D</option>
                                                                        </select>
                                                                    </div>
                                                                    <hr>
                                                                <?php endfor; ?>

                                                                <!-- Submit -->
                                                                <button type="submit" class="main-button">
                                                                    <i class="fa fa-save"></i> Create Quiz
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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