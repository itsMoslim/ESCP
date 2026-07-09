<?php
include 'header.php';
require_once 'db_connect.php';

// Helper: validate password complexity
function isValidPassword($password)
{
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $error_is_html = false;

    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } elseif (!isValidPassword($password)) {
        $error = "Password must contain letters and numbers, min 8 chars.";
    } else {
        $table = ($role === 'Coach') ? 'coaches' : (($role === 'Player') ? 'players' : null);

        if ($table) {
            $stmt = $conn->prepare("SELECT username, password_hash, account_active FROM $table WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // First check: account status
                if (!$user['account_active']) {
                    $error = 'Your account is inactive. Please contact <a href="support.php">support</a>.';
                    $error_is_html = true;
                }
                // Second check: password
                elseif (!password_verify($password, $user['password_hash'])) {
                    $error = "Incorrect password. Please try again.";
                } else {
                    // Fetch profile_id
                    $profileStmt = $conn->prepare("SELECT profile_id FROM profiles WHERE username = ?");
                    $profileStmt->bind_param("s", $username);
                    $profileStmt->execute();
                    $profileResult = $profileStmt->get_result();

                    if ($profileResult->num_rows === 1) {
                        $profile = $profileResult->fetch_assoc();

                        session_regenerate_id(true);
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;
                        $_SESSION['profile_id'] = $profile['profile_id'];

                        if ($role === 'Coach') {
                            header("Location: c_dashboard.php");
                        } else {
                            header("Location: p_dashboard.php");
                        }
                        exit();
                    } else {
                        $error = "Profile not found.";
                    }
                }
            } else {
                $error = "User not found.";
            }
        } else {
            $error = "Invalid role selected.";
        }
    }
}

$selectedRole = $_POST['role'] ?? '';
?>



<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <div class="page-content">
                <!-- ***** Banner Start ***** -->
                <div class="main-banner">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="header-text">
                                <h6><em>Secure access to your personalized dashboard.</em></h6>
                                <h4>LOGIN TO CONTINUE</h4>
                                <div class="line"></div>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- ***** Banner End ***** -->


                <!-- ***** Content Start ***** -->
                <div class="escp_content1">

                    <div class="row">
                        <div class="col-lg-12">

                            <!-- ***** Login Form Start ***** -->
                            <div class="escp_form">
                                <div class="container my-5">
                                    <div class="row justify-content-center">
                                        <div class="col-lg-8">
                                            <?php if (!empty($error)): ?>
                                                <div class="alert alert-danger">
                                                    <?php echo !empty($error_is_html) ? $error : htmlspecialchars($error); ?>
                                                </div>
                                            <?php endif; ?>
                                            <form method="POST" action="">
                                                <div class="mb-3">
                                                    <label for="username" class="form-label">Username</label>
                                                    <input type="text" class="form-control" id="username" name="username" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="password" class="form-label">Password</label>
                                                    <input type="password" class="form-control" id="password" name="password" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Select Role</label>
                                                    <input type="hidden" id="role" name="role" value="<?php echo htmlspecialchars($selectedRole); ?>" required>
                                                    <button type="button" class="btn <?php echo ($selectedRole === 'Player') ? 'btn-primary' : 'btn-outline-primary'; ?> me-2 role-option-btn" data-role="Player">Player</button>
                                                    <button type="button" class="btn <?php echo ($selectedRole === 'Coach') ? 'btn-primary' : 'btn-outline-primary'; ?> role-option-btn" data-role="Coach">Coach</button>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fa fa-sign-in-alt"></i> Login
                                                </button><br><br>

                                                <div class="mt-3 text-center">
                                                    <a href="p_registration.php" class="text-decoration-none">
                                                         Register As Player
                                                    </a>
                                                    &nbsp; &nbsp;
                                                      <a href="c_registration.php" class="text-decoration-none">
                                                         Register As Coach
                                                    </a>
                                                    &nbsp; &nbsp;
                                                    <a href="forgot_password.php" class="text-decoration-none">
                                                         Forgot Password?
                                                    </a>
                                                </div>

                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- ***** Login Form End ***** -->



                        </div>
                    </div>

                </div>
                
    <!-- ***** Content End ***** -->

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

<script>
document.querySelectorAll('.role-option-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var selected = this.getAttribute('data-role');
        document.getElementById('role').value = selected;

        document.querySelectorAll('.role-option-btn').forEach(function(otherBtn) {
            otherBtn.classList.remove('btn-primary');
            otherBtn.classList.add('btn-outline-primary');
        });

        this.classList.remove('btn-outline-primary');
        this.classList.add('btn-primary');
    });
});
</script>