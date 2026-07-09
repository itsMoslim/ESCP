<?php
include 'db_connect.php';

$data   = json_decode(file_get_contents("php://input"), true);
$gameId = (int)($data['game_id'] ?? 0);
$roleId = (int)($data['role_id'] ?? 0);
$rankId = (int)($data['rank_id'] ?? 0);
$budget = (int) filter_var($data['budget'], FILTER_SANITIZE_NUMBER_INT);
$goal   = $data['goal'] ?? '';

error_log("Filters: gameId=$gameId, roleId=$roleId, rankId=$rankId, budget=$budget, goal=$goal");

$sql = "SELECT c.fullname, c.hourly_rate, p.rating, p.profile_picture, g.name AS game,
               r.rank_name, ro.role_name, pp.coaching_goal
        FROM coaches c
        JOIN profiles p ON c.username = p.username
        JOIN user_games ug ON p.profile_id = ug.profile_id
        JOIN games g ON ug.game_id = g.game_id
        JOIN game_ranks r ON ug.rank_id = r.rank_id
        JOIN game_roles ro ON ug.role_id = ro.role_id
        LEFT JOIN player_preferences pp ON p.profile_id = pp.profile_id
        WHERE g.game_id=? AND ro.role_id=? AND r.rank_id=? 
              AND c.hourly_rate <= ?
              AND c.verification_flag = TRUE
              AND c.account_active = TRUE";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $gameId, $roleId, $rankId, $budget);
$stmt->execute();
$result = $stmt->get_result();

$html = '<div class="row mt-3">';
$found = false;
while ($row = $result->fetch_assoc()) {
    $found = true;
    $html .= '<div class="col-lg-4">
                <div class="item">
                  <img src="assets/pictures/'.htmlspecialchars($row['profile_picture']).'" 
                       alt="Coach Picture" class="img-fluid rounded shadow-sm">
                  <h5>'.htmlspecialchars($row['fullname']).'</h5>
                  <p>Game: '.htmlspecialchars($row['game']).' | Role: '.htmlspecialchars($row['role_name']).'</p>
                  <p>Rank: '.htmlspecialchars($row['rank_name']).' | Rate: '.$row['hourly_rate'].' SAR/hr</p>
                  <p>Rating: '.$row['rating'].'/5</p>
                </div>
              </div>';
}
$html .= '</div>';

if ($found) {
    echo $html;
    exit;
}

// --- Fallback: no matches -> suggest all coaches for the chosen game ---
$gameName = 'selected game';
if ($gameId > 0) {
    $gameStmt = $conn->prepare("SELECT name FROM games WHERE game_id = ?");
    $gameStmt->bind_param("i", $gameId);
    $gameStmt->execute();
    $gameRes = $gameStmt->get_result();
    if ($gameRes && $gameRes->num_rows === 1) {
        $gameName = $gameRes->fetch_assoc()['name'];
    }
}

echo '<div class="mt-3">';
echo '<div class="alert alert-warning" role="alert">';
echo 'No coaches were found with the matching criteria.';
echo '</div>';
echo '<p>Here are the top coaches for <strong>' . htmlspecialchars($gameName) . '</strong>:</p>';
echo '</div>';

$fallbackSql = "SELECT c.fullname, c.hourly_rate, p.rating, p.profile_picture, g.name AS game,
                       r.rank_name, ro.role_name
                FROM coaches c
                JOIN profiles p ON c.username = p.username
                JOIN user_games ug ON p.profile_id = ug.profile_id
                JOIN games g ON ug.game_id = g.game_id
                JOIN game_ranks r ON ug.rank_id = r.rank_id
                JOIN game_roles ro ON ug.role_id = ro.role_id
                WHERE g.game_id = ?
                  AND c.verification_flag = TRUE
                  AND c.account_active = TRUE
                ORDER BY p.rating DESC, c.hourly_rate ASC
                LIMIT 12";

$fbStmt = $conn->prepare($fallbackSql);
$fbStmt->bind_param("i", $gameId);
$fbStmt->execute();
$fbRes = $fbStmt->get_result();

$fbHtml = '<div class="row mt-3">';
while ($row = $fbRes->fetch_assoc()) {
    $fbHtml .= '<div class="col-lg-4">
                <div class="item">
                  <img src="assets/pictures/'.htmlspecialchars($row['profile_picture']).'" 
                       alt="Coach Picture" class="img-fluid rounded shadow-sm">
                  <h5>'.htmlspecialchars($row['fullname']).'</h5>
                  <p>Game: '.htmlspecialchars($row['game']).' | Role: '.htmlspecialchars($row['role_name']).'</p>
                  <p>Rank: '.htmlspecialchars($row['rank_name']).' | Rate: '.$row['hourly_rate'].' SAR/hr</p>
                  <p>Rating: '.$row['rating'].'/5</p>
                </div>
              </div>';
}
$fbHtml .= '</div>';

echo $fbHtml;
