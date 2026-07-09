<?php
include 'header.php';
require_once 'db_connect.php';

// Helper: validate password complexity
function isValidPassword($password) {
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password);
}

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $role     = $_POST['role'] ?? '';
    $answer   = $_POST['security_answer'] ?? '';
    $newPass  = $_POST['new_password'] ?? '';

    if (empty($username) || empty($role)) {
        $error = "Please fill in username and role.";
    } else {
        // Step 1: fetch profile_id
        $profileStmt = $conn->prepare("SELECT profile_id FROM profiles WHERE username = ? AND profile_type = ?");
        $profileStmt->bind_param("ss", $username, $role);
        $profileStmt->execute();
        $profileResult = $profileStmt->get_result();

        if ($profileResult->num_rows === 1) {
            $profile = $profileResult->fetch_assoc();
            $profileId = $profile['profile_id'];

            // Step 2: fetch security question
            $secStmt = $conn->prepare("SELECT q.question_text, u.answer_hash 
                                       FROM user_security_questions u 
                                       JOIN security_questions q ON u.question_id = q.question_id 
                                       WHERE u.profile_id = ?");
            $secStmt->bind_param("i", $profileId);
            $secStmt->execute();
            $secResult = $secStmt->get_result();

            if ($secResult->num_rows === 1) {
                $secRow = $secResult->fetch_assoc();
                $questionText = $secRow['question_text'];
                $storedHash   = $secRow['answer_hash'];

                // If answer submitted
                if (!empty($answer) && !empty($newPass)) {
                    if (!password_verify($answer, $storedHash)) {
                        $error = "Incorrect answer. Please try again.";
                    } elseif (!isValidPassword($newPass)) {
                        $error = "New password must contain letters and numbers, min 8 chars.";
                    } else {
                        // Reset password in correct table
                        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                        $table = ($role === 'Coach') ? 'coaches' : 'players';
                        $updateStmt = $conn->prepare("UPDATE $table SET password_hash = ? WHERE username = ?");
                        $updateStmt->bind_param("ss", $newHash, $username);
                        $updateStmt->execute();

                        $success = "Password reset successful. You may now login.";
                        // Clear question so form doesn't show again
                        unset($questionText);
                    }
                }
            } else {
                $error = "Security question not found.";
            }
        } else {
            $error = "User not found.";
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
                <h6><em>Reset your password securely.</em></h6>
                <h4>FORGOT PASSWORD</h4>
                <div class="line"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form -->
        <div class="escp_content1">
          <div class="row">
            <div class="col-lg-12">
              <div class="escp_form">
                <div class="container my-5">
                  <div class="row justify-content-center">
                    <div class="col-lg-8">
                      <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                      <?php endif; ?>
                      <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                      <?php endif; ?>

                      <?php if (empty($success)): ?>
                        <form method="POST" action="">
                          <?php if (empty($questionText)): ?>
                            <!-- Step 1: Enter username + role -->
                            <div class="mb-3">
                              <label for="username" class="form-label">Username *</label>
                              <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                              <label for="role" class="form-label">Select Role *</label>
                              <select class="form-control" id="role" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="Coach" <?php if(($role ?? '')==='Coach') echo 'selected'; ?>>Coach</option>
                                <option value="Player" <?php if(($role ?? '')==='Player') echo 'selected'; ?>>Player</option>
                              </select>
                            </div>
                            <button type="submit" class="main-button">
                              <i class="fa fa-search"></i> Find Security Question
                            </button>
                          <?php else: ?>
                            <!-- Step 2: Show question + answer + new password -->
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">

                            <div class="mb-3">
                              <label class="form-label">Security Question</label>
                              <p><strong><?php echo htmlspecialchars($questionText); ?></strong></p>
                            </div>
                            <div class="mb-3">
                              <label for="security_answer" class="form-label">Your Answer *</label>
                              <input type="text" class="form-control" id="security_answer" name="security_answer" required>
                            </div>
                            <div class="mb-3">
                              <label for="new_password" class="form-label">New Password *</label>
                              <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <button type="submit" class="main-button">
                              <i class="fa fa-key"></i> Reset Password
                            </button>
                          <?php endif; ?>
                        </form>
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

<?php include 'footer.php'; ?>
