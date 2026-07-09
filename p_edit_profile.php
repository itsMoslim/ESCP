<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Player");
require_once "db_connect.php";

// Fetch current player data
$username = $_SESSION['username'];
$stmt = $conn->prepare("
    SELECT pl.fullname, pl.email,
           p.profile_id, p.bio, p.experience_years, p.profile_picture, p.rating,
           ug.rank_id, ug.role_id, g.name AS game_name,
           pp.coaching_goal
    FROM players pl
    JOIN profiles p ON pl.username = p.username
    LEFT JOIN user_games ug ON p.profile_id = ug.profile_id
    LEFT JOIN games g ON ug.game_id = g.game_id
    LEFT JOIN player_preferences pp ON p.profile_id = pp.profile_id
    WHERE pl.username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$player = $stmt->get_result()->fetch_assoc();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname   = trim($_POST['fullname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');
    $experience = intval($_POST['experience_years'] ?? 0);
    $rankId     = intval($_POST['rank_id'] ?? 0);
    $roleId     = intval($_POST['role_id'] ?? 0);
    $goal       = trim($_POST['coaching_goal'] ?? '');
    $allowedGoals = ['Improving aim', 'Team coordination', 'Better mechanics and positioning'];

    if (!in_array($goal, $allowedGoals, true)) {
        $error = "Invalid coaching goal selected.";
    } else {
        $conn->begin_transaction();
        try {
        // Update players
        $stmt = $conn->prepare("UPDATE players SET fullname=?, email=? WHERE username=?");
        $stmt->bind_param("sss", $fullname, $email, $username);
        $stmt->execute();

        // Handle profile picture upload
        $picture = $player['profile_picture'];
        if (!empty($_FILES['profile_picture']['name'])) {
            $targetDir = "assets/pictures/";
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $newFile = $username . "." . $ext;
            $targetFile = $targetDir . $newFile;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                // Delete old picture only if it exists and is different
                if (!empty($picture) && file_exists($targetDir . $picture) && $picture !== $newFile) {
                    unlink($targetDir . $picture);
                }
                $picture = $newFile;
            } else {
                throw new Exception("Failed to upload new profile picture.");
            }
        }

        // Update profiles
        $stmt = $conn->prepare("UPDATE profiles SET bio=?, experience_years=?, profile_picture=? WHERE username=?");
        $stmt->bind_param("siss", $bio, $experience, $picture, $username);
        $stmt->execute();

        // Update user_games (rank and role only)
        $stmt = $conn->prepare("UPDATE user_games SET rank_id=?, role_id=? WHERE profile_id=?");
        $stmt->bind_param("iii", $rankId, $roleId, $player['profile_id']);
        $stmt->execute();

        // Update player_preferences (coaching goal)
        $stmt = $conn->prepare("UPDATE player_preferences SET coaching_goal=? WHERE profile_id=?");
        $stmt->bind_param("si", $goal, $player['profile_id']);
        $stmt->execute();

            $conn->commit();
            $success = "Profile updated successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Update failed: " . $e->getMessage();
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
                                <h6><em>Edit your profile details</em></h6>
                                <h4>Player Profile Edit</h4>
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
                                            <h4>Edit Profile</h4><br>

                                            <div class="escp_form">
                                                <div class="container my-5">
                                                    <div class="row justify-content-center">
                                                        <div class="col-lg-12">
                                                            <?php if (!empty($error)): ?>
                                                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                                            <?php elseif (!empty($success)): ?>
                                                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                                            <?php endif; ?>

                                                            <form method="POST" enctype="multipart/form-data">
                                                                <!-- Full Name -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Full Name</label>
                                                                    <input type="text" class="form-control" name="fullname" value="<?php echo htmlspecialchars($player['fullname']); ?>" required>
                                                                </div>

                                                                <!-- Email -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Email</label>
                                                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($player['email']); ?>" required>
                                                                </div>

                                                                <!-- Bio -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Bio</label>
                                                                    <textarea class="form-control" name="bio" required><?php echo htmlspecialchars($player['bio']); ?></textarea>
                                                                </div>

                                                                <!-- Experience -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Experience (years)</label>
                                                                    <input type="number" class="form-control" name="experience_years" value="<?php echo (int)$player['experience_years']; ?>" required>
                                                                </div>

                                                                <!-- Game (locked) -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Game</label>
                                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($player['game_name']); ?>" disabled>
                                                                </div>

                                                                <!-- Rank -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Rank</label>
                                                                    <select class="form-control" name="rank_id" required>
                                                                        <?php
                                                                        $ranks = $conn->query("SELECT rank_id, rank_name FROM game_ranks WHERE game_id=(SELECT game_id FROM user_games WHERE profile_id={$player['profile_id']} LIMIT 1)");
                                                                        while ($r = $ranks->fetch_assoc()) {
                                                                            $sel = ($player['rank_id'] == $r['rank_id']) ? "selected" : "";
                                                                            echo "<option value='{$r['rank_id']}' $sel>" . htmlspecialchars($r['rank_name']) . "</option>";
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>

                                                                <!-- Role -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Role</label>
                                                                    <select class="form-control" name="role_id" required>
                                                                        <?php
                                                                        $roles = $conn->query("SELECT role_id, role_name FROM game_roles WHERE game_id=(SELECT game_id FROM user_games WHERE profile_id={$player['profile_id']} LIMIT 1)");
                                                                        while ($r = $roles->fetch_assoc()) {
                                                                            $sel = ($player['role_id'] == $r['role_id']) ? "selected" : "";
                                                                            echo "<option value='{$r['role_id']}' $sel>" . htmlspecialchars($r['role_name']) . "</option>";
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>

                                                                <!-- Coaching Goal -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Coaching Goal</label>
                                                                    <select class="form-control" name="coaching_goal" required>
                                                                        <?php
                                                                        $goalOptions = ['Improving aim', 'Team coordination', 'Better mechanics and positioning'];
                                                                        foreach ($goalOptions as $goalOption) {
                                                                            $sel = (($player['coaching_goal'] ?? '') === $goalOption) ? "selected" : "";
                                                                            echo "<option value='" . htmlspecialchars($goalOption) . "' $sel>" . htmlspecialchars($goalOption) . "</option>";
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>

                                                                <!-- Profile Picture -->
                                                                <div class="mb-3">
                                                                    <label class="form-label">Profile Picture</label><br>
                                                                    <?php if (!empty($player['profile_picture'])): ?>
                                                                        <img src="assets/pictures/<?php echo htmlspecialchars($player['profile_picture']); ?>" alt="Profile Picture" width="120"><br><br>
                                                                    <?php else: ?>
                                                                        <p>No picture uploaded yet.</p>
                                                                    <?php endif; ?>
                                                                    <input type="file" class="form-control" name="profile_picture" accept="image/*">
                                                                </div>

                                                                <!-- Submit -->
                                                                <button type="submit" class="main-button">
                                                                    <i class="fa fa-save"></i> Update Profile
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
