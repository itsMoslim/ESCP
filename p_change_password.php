<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Player");
require_once "db_connect.php";

// Fetch current player data
$username = $_SESSION['username'];
$stmt = $conn->prepare("
    SELECT pl.password_hash,
           p.profile_id,
           usq.question_id, usq.answer_hash,
           sq.question_text
    FROM players pl
    JOIN profiles p ON pl.username = p.username
    LEFT JOIN user_security_questions usq ON p.profile_id = usq.profile_id
    LEFT JOIN security_questions sq ON usq.question_id = sq.question_id
    WHERE pl.username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$error = "";
$success = "";

// Helper: validate password complexity
function isValidPassword($password) {
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword   = $_POST['old_password'] ?? '';
    $answer        = $_POST['security_answer'] ?? '';
    $newPassword   = $_POST['new_password'] ?? '';
    $confirmPass   = $_POST['confirm_password'] ?? '';

    try {
        // Verify old password
        if (!password_verify($oldPassword, $data['password_hash'])) {
            throw new Exception("Old password is incorrect.");
        }

        // Verify security answer
        if (!password_verify($answer, $data['answer_hash'])) {
            throw new Exception("Security answer is incorrect.");
        }

        // Validate new password
        if ($newPassword !== $confirmPass) {
            throw new Exception("New password and confirmation do not match.");
        }
        if (!isValidPassword($newPassword)) {
            throw new Exception("New password must contain letters and numbers only, min 8 chars.");
        }

        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE players SET password_hash=? WHERE username=?");
        $stmt->bind_param("ss", $newHash, $username);
        $stmt->execute();

        $success = "Password updated successfully. Please log in again.";
        session_destroy();
        header("Location: login.php?password_changed=1");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
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
                                <h6><em>Secure your account</em></h6>
                                <h4>Change Password</h4>
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
                                    <div class="col-lg-6">
                                        <div class="left-info">
                                            <h4>Change Password</h4><br>

                                            <div class="escp_form">
                                                <div class="container my-5">
                                                    <div class="row justify-content-center">
                                                        <div class="col-lg-12">
                                                            <?php if (!empty($error)): ?>
                                                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                                            <?php elseif (!empty($success)): ?>
                                                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                                            <?php endif; ?>

                                                            <form method="POST">
                                                                <!-- Old Password -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Old Password</label>
                                                                    <input type="password" class="form-control" name="old_password" required>
                                                                </div>

                                                                <!-- Security Question -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Security Question</label>
                                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['question_text']); ?>" disabled>
                                                                </div>

                                                                <!-- Security Answer -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Answer</label>
                                                                    <input type="text" class="form-control" name="security_answer" required>
                                                                </div>

                                                                <!-- New Password -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">New Password</label>
                                                                    <input type="password" class="form-control" name="new_password" required>
                                                                    <p>Alphanumeric only, min 8 characters.</p>
                                                                </div>

                                                                <!-- Confirm New Password -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Confirm New Password</label>
                                                                    <input type="password" class="form-control" name="confirm_password" required>
                                                                </div>

                                                                <!-- Submit -->
                                                                <button type="submit" class="main-button">
                                                                    <i class="fa fa-key"></i> Update Password
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="right-info">
                                            <h4>Control Hub</h4>
                                            <?php include 'p_menu.php'; ?>
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
