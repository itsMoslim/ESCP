<?php
include 'header.php';
require_once 'db_connect.php';

// Enable error reporting for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Helper: validate password complexity (letters + numbers only, min 8 chars)
function isValidPassword($password)
{
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password);
}

function saveOptionalProfilePicture(string $username, string $fieldName = 'profile_picture'): ?string
{
    if (empty($_FILES[$fieldName]['name']) || empty($_FILES[$fieldName]['tmp_name'])) {
        return null;
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new Exception("Invalid profile picture type. Allowed: JPG, JPEG, PNG, WEBP.");
    }

    $targetDir = "assets/pictures/";
    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true)) {
        throw new Exception("Failed to prepare profile picture directory.");
    }

    $safeUsername = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
    $fileName = "player_" . $safeUsername . "_" . time() . "." . $ext;
    $targetPath = $targetDir . $fileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        throw new Exception("Failed to upload profile picture.");
    }

    return $fileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $fullname   = trim($_POST['fullname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $bio        = trim($_POST['bio'] ?? '');
    $gameId     = intval($_POST['game_id'] ?? 0);
    $rankId     = intval($_POST['rank_id'] ?? 0);
    $roleId     = intval($_POST['role_id'] ?? 0);
    $goal       = trim($_POST['coaching_goal'] ?? '');
    $questionId = intval($_POST['security_question'] ?? 0);
    $answer     = $_POST['security_answer'] ?? '';
    $allowedGoals = ['Improving aim', 'Team coordination', 'Better mechanics and positioning'];
    // Keep defaults for DB-required columns that are removed from registration form.
    $experience = 0;

    if (empty($username) || empty($fullname) || empty($email) || empty($password) || empty($gameId) || empty($rankId) || empty($roleId) || empty($goal) || empty($questionId) || empty($answer)) {
        $error = "All fields marked * are mandatory.";
    } elseif (!isValidPassword($password)) {
        $error = "Password must contain letters and numbers only, min 8 chars.";
    } elseif (!in_array($goal, $allowedGoals, true)) {
        $error = "Invalid coaching goal selected.";
    } else {
        $conn->begin_transaction();
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $profilePicture = saveOptionalProfilePicture($username);

            // Insert into players
            $stmt = $conn->prepare("INSERT INTO players (username, fullname, email, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $fullname, $email, $passwordHash);
            $stmt->execute();

            // Insert into profiles
            $stmt = $conn->prepare("INSERT INTO profiles (profile_type, username, bio, experience_years, profile_picture) VALUES ('Player', ?, ?, ?, ?)");
            $stmt->bind_param("ssis", $username, $bio, $experience, $profilePicture);
            $stmt->execute();
            $profileId = $conn->insert_id;

            // Insert into user_games
            $stmt = $conn->prepare("INSERT INTO user_games (profile_id, game_id, rank_id, role_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $profileId, $gameId, $rankId, $roleId);
            $stmt->execute();

            // Insert into player_preferences
            $stmt = $conn->prepare("INSERT INTO player_preferences (profile_id, coaching_goal) VALUES (?, ?)");
            $stmt->bind_param("is", $profileId, $goal);
            $stmt->execute();

            // Insert into user_security_questions
            $answerHash = password_hash($answer, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user_security_questions (profile_id, question_id, answer_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $profileId, $questionId, $answerHash);
            $stmt->execute();

            $conn->commit();
            header("Location: login.php?registered=success");
            exit();
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            if (!empty($profilePicture)) {
                $uploadedPath = "assets/pictures/" . $profilePicture;
                if (is_file($uploadedPath)) {
                    unlink($uploadedPath);
                }
            }

            $msg = strtolower($e->getMessage());
            if ((int)$e->getCode() === 1062 && (str_contains($msg, 'email') || str_contains($msg, 'players.email'))) {
                $error = "This email is already registered. please login.";
            } else {
                $error = "Registration failed: " . $e->getMessage();
            }
        } catch (Exception $e) {
            $conn->rollback();
            if (!empty($profilePicture)) {
                $uploadedPath = "assets/pictures/" . $profilePicture;
                if (is_file($uploadedPath)) {
                    unlink($uploadedPath);
                }
            }
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
                                <h4>Player Registration</h4>
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
                                            <form method="POST" action="" enctype="multipart/form-data">
                                                <!-- Username -->
                                                <div class="mb-3">
                                                    <label class="form-label">Username *</label>
                                                    <input type="text" class="form-control" name="username" required>
                                                </div>

                                                <!-- Full Name -->
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name *</label>
                                                    <input type="text" class="form-control" name="fullname" required>
                                                </div>

                                                <!-- Email -->
                                                <div class="mb-3">
                                                    <label class="form-label">Email *</label>
                                                    <input type="email" class="form-control" name="email" required>
                                                </div>

                                                <!-- Password -->
                                                <div class="mb-3">
                                                    <label class="form-label">Password *</label>
                                                    <input type="password" class="form-control" name="password" required>
                                                    <p>Alphanumeric only, min 8 characters.</p>
                                                </div>

                                                <!-- Bio -->
                                                <div class="mb-3">
                                                    <label class="form-label">Bio</label>
                                                    <textarea class="form-control" name="bio"></textarea>
                                                </div>

                                                <!-- Profile Picture (optional) -->
                                                <div class="mb-3">
                                                    <label class="form-label">Profile Picture</label>
                                                    <input type="file" class="form-control" name="profile_picture" accept="image/*">
                                                </div>

                                                <!-- Game -->
                                                <div class="mb-3">
                                                    <label class="form-label">Select Game *</label>
                                                    <select class="form-control" name="game_id" id="game_id" required>
                                                        <option value="">-- Select Game --</option>
                                                        <?php
                                                        $gResult = $conn->query("SELECT game_id, name FROM games");
                                                        while ($row = $gResult->fetch_assoc()) {
                                                            echo "<option value='{$row['game_id']}'>" . htmlspecialchars($row['name']) . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <!-- Rank -->
                                                <div class="mb-3">
                                                    <label class="form-label">Select Rank *</label>
                                                    <select class="form-control" name="rank_id" id="rank_id" required>
                                                        <option value="">-- Select Rank --</option>
                                                    </select>
                                                </div>

                                                <!-- Role -->
                                                <div class="mb-3">
                                                    <label class="form-label">Select Role *</label>
                                                    <select class="form-control" name="role_id" id="role_id" required>
                                                        <option value="">-- Select Role --</option>
                                                    </select>
                                                </div>

                                                <!-- Coaching Goal -->
                                                <div class="mb-3">
                                                    <label class="form-label">Coaching Goal *</label>
                                                    <select class="form-control" name="coaching_goal" required>
                                                        <option value="">-- Select Coaching Goal --</option>
                                                        <option value="Improving aim">Improving aim</option>
                                                        <option value="Team coordination">Team coordination</option>
                                                        <option value="Better mechanics and positioning">Better mechanics and positioning</option>
                                                    </select>
                                                </div>



                                                <!-- Security Question -->
                                                <div class="mb-3">
                                                    <label class="form-label">Security Question *</label>
                                                    <select class="form-control" name="security_question" required>
                                                        <?php
                                                        $qResult = $conn->query("SELECT question_id, question_text FROM security_questions");
                                                        while ($row = $qResult->fetch_assoc()) {
                                                            echo "<option value='{$row['question_id']}'>" . htmlspecialchars($row['question_text']) . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <!-- Security Answer -->
                                                <div class="mb-3">
                                                    <label class="form-label">Answer *</label>
                                                    <input type="text" class="form-control" name="security_answer" required>
                                                </div>

                                                <!-- Submit -->
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fa fa-user-plus"></i> Register
                                                </button>
                                            </form>
                                            <script>
                                                document.getElementById('game_id').addEventListener('change', function() {
                                                    let gameId = this.value;

                                                    // Fetch ranks for selected game
                                                    fetch('get_ranks.php?game_id=' + gameId)
                                                        .then(res => res.json())
                                                        .then(data => {
                                                            let rankSelect = document.getElementById('rank_id');
                                                            rankSelect.innerHTML = '<option value="">-- Select Rank --</option>';
                                                            data.forEach(item => {
                                                                let opt = document.createElement('option');
                                                                opt.value = item.rank_id;
                                                                opt.textContent = item.rank_name;
                                                                rankSelect.appendChild(opt);
                                                            });
                                                        });

                                                    // Fetch roles for selected game
                                                    fetch('get_roles.php?game_id=' + gameId)
                                                        .then(res => res.json())
                                                        .then(data => {
                                                            let roleSelect = document.getElementById('role_id');
                                                            roleSelect.innerHTML = '<option value="">-- Select Role --</option>';
                                                            data.forEach(item => {
                                                                let opt = document.createElement('option');
                                                                opt.value = item.role_id;
                                                                opt.textContent = item.role_name;
                                                                roleSelect.appendChild(opt);
                                                            });
                                                        });
                                                });
                                            </script>

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