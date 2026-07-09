<?php
require_once 'db_connect.php';

if (isset($_GET['count'])) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM articles");
    $row = $result->fetch_assoc();
    echo $row['total'];
    exit;
}

$offset = intval($_GET['offset'] ?? 0);
$limit = intval($_GET['limit'] ?? 3);

$result = $conn->query("
    SELECT a.article_id, a.title, a.content, a.created_at, p.username, p.profile_type
    FROM articles a
    JOIN profiles p ON a.user_id = p.profile_id
    ORDER BY a.created_at DESC
    LIMIT $offset, $limit
");

while ($row = $result->fetch_assoc()) {
    $words = explode(" ", $row['content']);
    $preview = implode(" ", array_slice($words, 0, 70));
    echo "
    <div class='article-preview'>
      <div class='d-flex justify-content-between small text-muted'>
        <span>".date("M d, Y H:i", strtotime($row['created_at']))."</span>
        <span>".htmlspecialchars($row['username'])." (".$row['profile_type'].")</span>
      </div>
      <h5>
        <a href='article.php?id=".$row['article_id']."'>".htmlspecialchars($row['title'])."</a>
      </h5>
      <p>".htmlspecialchars($preview)."...</p>
      <a href='article.php?id=".$row['article_id']."' class='main-button'>Read More</a>
      <hr>
    </div>
    ";
}
