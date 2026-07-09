<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

$gameId = intval($_GET['game_id'] ?? 0);
$data = [];

if ($gameId > 0) {
    $result = $conn->query("SELECT rank_id, rank_name FROM game_ranks WHERE game_id = $gameId");
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
?>
