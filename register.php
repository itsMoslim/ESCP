<?php
include 'header.php';
require_once 'db_connect.php';

// Helper: validate password complexity
function isValidPassword($password) {
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $fullname   = trim($_POST['fullname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = $_POST['role'] ?? '';
    $bio        = trim($_POST['bio'] ?? '');
    $experience = intval($_POST['experience_years'] ?? 0);
    $education  = $_POST['education'] ?? '';
    $questionId = intval($_POST['security_question'] ?? 0);
    $answer     = $_POST['security_answer'] ?? '';

    if (empty($username) || empty($fullname) || empty($email) || empty($password) || empty($role) || empty($education) || empty($questionId) || empty($answer)) {
        $error = "All fields marked * are mandatory.";
    } elseif (!isValidPassword($password)) {
        $error = "Password must contain letters and numbers, min 8 chars.";
    } else {
        $conn->begin_transaction();
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert into coaches or players
            if ($role === 'Coach') {
                $stmt = $conn->prepare("INSERT INTO coaches (username, fullname, email, password_hash) VALUES (?, ?, ?, ?)");
            } elseif ($role === 'Player') {
                $stmt = $conn->prepare("INSERT INTO players (username, fullname, email, password_hash) VALUES (?, ?, ?, ?)");
            } else {
                throw new Exception("Invalid role selected.");
            }
            $stmt->bind_param("ssss", $username, $fullname, $email, $passwordHash);
            $stmt->execute();

            // Insert into profiles
            $stmt = $conn->prepare("INSERT INTO profiles (profile_type, username, bio, experience_years, education) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssds", $role, $username, $bio, $experience, $education);
            $stmt->execute();
            $profileId = $conn->insert_id;

            // Insert into user_security_questions
            $answerHash = password_hash($answer, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user_security_questions (profile_id, question_id, answer_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $profileId, $questionId, $answerHash);
            $stmt->execute();

            $conn->commit();
            header("Location: login.php?registered=success");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Registration failed: " . $e->getMessage();
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
                <h6><em>Create your account securely.</em></h6>
                <h4>REGISTER NOW</h4>
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
                      <form method="POST" action="">
                        <div class="mb-3">
                          <label for="username" class="form-label">Username *</label>
                          <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                          <label for="fullname" class="form-label">Full Name *</label>
                          <input type="text" class="form-control" id="fullname" name="fullname" required>
                        </div>
                        <div class="mb-3">
                          <label for="email" class="form-label">Email *</label>
                          <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                          <label for="password" class="form-label">Password *</label>
                          <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                          <label for="role" class="form-label">Select Role *</label>
                          <select class="form-control" id="role" name="role" required>
                            <option value="">-- Select Role --</option>
                            <option value="Coach">Coach</option>
                            <option value="Player">Player</option>
                          </select>
                        </div>
                        <div class="mb-3">
                          <label for="bio" class="form-label">Bio</label>
                          <textarea class="form-control" id="bio" name="bio"></textarea>
                        </div>
                        <div class="mb-3">
                          <label for="experience_years" class="form-label">Experience (years)</label>
                          <input type="number" class="form-control" id="experience_years" name="experience_years">
                        </div>
                        <div class="mb-3">
                          <label for="education" class="form-label">Education *</label>
                          <select class="form-control" id="education" name="education" required>
                            <option value="">-- Select Education --</option>
                            <option value="HighSchool">High School</option>
                            <option value="Bachelor">Bachelor</option>
                            <option value="Master">Master</option>
                            <option value="PhD">PhD</option>
                            <option value="Diploma">Diploma</option>
                            <option value="Other">Other</option>
                          </select>
                        </div>
                        <div class="mb-3">
                          <label for="security_question" class="form-label">Security Question *</label>
                          <select class="form-control" id="security_question" name="security_question" required>
                            <?php
                            $qResult = $conn->query("SELECT question_id, question_text FROM security_questions");
                            while ($row = $qResult->fetch_assoc()) {
                                echo "<option value='{$row['question_id']}'>" . htmlspecialchars($row['question_text']) . "</option>";
                            }
                            ?>
                          </select>
                        </div>
                        <div class="mb-3">
                          <label for="security_answer" class="form-label">Answer *</label>
                          <input type="text" class="form-control" id="security_answer" name="security_answer" required>
                        </div>
                        <button type="submit" class="main-button">
                          <i class="fa fa-user-plus"></i> Register
                        </button>
                      </form>
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
