<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Coach");
require_once "db_connect.php";

$coach_id = $_SESSION['profile_id'];
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = intval($_POST['game_id']);
    $session_date = $_POST['session_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $hourly_rate = floatval($_POST['hourly_rate']);

    try {
        $stmt = $conn->prepare("
            INSERT INTO solo_sessions (coach_profile_id, game_id, session_date, start_time, end_time, hourly_rate, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Available')
        ");
        $stmt->bind_param("iisssd", $coach_id, $game_id, $session_date, $start_time, $end_time, $hourly_rate);
        $stmt->execute();
        $success = "Solo session created successfully.";
    } catch (Exception $e) {
        $error = "Error creating session: " . $e->getMessage();
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <div class="page-content">
                <div class="main-banner">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="header-text">
                                <h6><em>Create Session</em></h6>
                                <h4>Create Solo Session</h4>
                                <div class="line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="game-details">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2>Welcome Coach <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></h2>
                        </div>
                        <div class="col-lg-12">
                            <div class="content">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="left-info">
                                            <h4>Solo Session Form</h4><br>

                                            <?php if (!empty($error)): ?>
                                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                            <?php elseif (!empty($success)): ?>
                                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                            <?php endif; ?>

                                            <div class="escp_form">
                                                <form method="POST">
                                                    <div class="mb-3">
                                                        <label class="form-label">Game</label>
                                                        <select name="game_id" class="form-control" required>
                                                            <?php
                                                            $games = $conn->query("SELECT game_id, name FROM games");
                                                            while ($g = $games->fetch_assoc()) {
                                                                echo "<option value='{$g['game_id']}'>" . htmlspecialchars($g['name']) . "</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Date</label>
                                                        <input type="date" name="session_date" class="form-control" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Start Time</label>
                                                        <input type="time" name="start_time" class="form-control" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">End Time</label>
                                                        <input type="time" name="end_time" class="form-control" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Hourly Rate (SAR)</label>
                                                        <input type="number" step="0.01" name="hourly_rate" class="form-control" required>
                                                    </div>
                                                    <button type="submit" class="main-button">
                                                        <i class="fa fa-plus"></i> Create Solo Session
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="right-info">
                                            <h4>Control Hub</h4>
                                            <?php include 'c_menu.php'; ?>
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
