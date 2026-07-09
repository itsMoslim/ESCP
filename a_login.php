<?php
include 'header.php';
require_once 'db_connect.php';

// Helper: validate password complexity
function isValidPassword($password) {
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password);
}

session_start();

// If already logged in as admin, redirect
if (isset($_SESSION['username']) && ($_SESSION['role'] ?? '') === 'Admin') {
    header("Location: a_dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (!isValidPassword($password)) {
        $error = "Password must contain letters and numbers, min 8 chars.";
    } else {
        $stmt = $conn->prepare("SELECT username, password_hash FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (!password_verify($password, $user['password_hash'])) {
                $error = "Incorrect password. Please try again.";
            } else {
                // Successful login
                session_regenerate_id(true);
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'Admin';
                $_SESSION['last_activity'] = time();

                header("Location: a_dashboard.php");
                exit();
            }
        } else {
            $error = "Admin user not found.";
        }
    }
}
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
                                <h6><em>Administrator secure access.</em></h6>
                                <h4>ADMIN LOGIN</h4>
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
                            <!-- ***** Admin Login Form Start ***** -->
                            <div class="escp_form">
                                <div class="container my-5">
                                    <div class="row justify-content-center">
                                        <div class="col-lg-8">
                                            <?php if (!empty($error)): ?>
                                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
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
                                                <button type="submit" class="main-button">
                                                    <i class="fa fa-sign-in-alt"></i> Login
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- ***** Admin Login Form End ***** -->
                        </div>
                    </div>
                </div>
                <!-- ***** Content End ***** -->
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>
