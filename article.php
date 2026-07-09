<?php
include 'header.php';
require_once 'db_connect.php';

$role = $_SESSION['role'] ?? null;
$username = $_SESSION['username'] ?? null;
$profile_id = $_SESSION['profile_id'] ?? null;


$article_id = intval($_GET['id'] ?? 0);

// Fetch article
$stmt = $conn->prepare("
    SELECT a.title, a.content, a.created_at, a.updated_at, p.username, p.profile_type
    FROM articles a
    JOIN profiles p ON a.user_id = p.profile_id
    WHERE a.article_id = ?
");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();

// Handle new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($role === 'Coach' || $role === 'Player')) {
    $comment = trim($_POST['comment'] ?? '');
    $wordCount = str_word_count($comment);

    if ($wordCount > 300) {
        $error = "Comment must be 300 words or less.";
    } elseif (!empty($comment)) {
        $cstmt = $conn->prepare("INSERT INTO comments (article_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $cstmt->bind_param("iis", $article_id, $profile_id, $comment);
        $cstmt->execute();
        header("Location: article.php?id=" . $article_id); // reload to show new comment
        exit();
    }
}

// Fetch comments
$cstmt = $conn->prepare("
    SELECT c.content, c.created_at, p.username, p.profile_type
    FROM comments c
    JOIN profiles p ON c.user_id = p.profile_id
    WHERE c.article_id = ?
    ORDER BY c.created_at ASC
");
$cstmt->bind_param("i", $article_id);
$cstmt->execute();
$comments = $cstmt->get_result();
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
                <h6><em>Community Blog</em></h6>
                <h4>Article Details</h4>
                <div class="line"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Article -->
        <div class="escp_content1">
          <?php if ($article): ?>
            <div class="article-full">
              <div class="d-flex justify-content-between small text-muted">
                <span>Published: <?php echo date("M d, Y H:i", strtotime($article['created_at'])); ?></span>
                <span>By <?php echo htmlspecialchars($article['username']); ?> (<?php echo $article['profile_type']; ?>)</span>
              </div>
              <?php if ($article['updated_at']): ?>
                <div class="small text-muted">Updated: <?php echo date("M d, Y H:i", strtotime($article['updated_at'])); ?></div>
              <?php endif; ?>

              <h3><?php echo htmlspecialchars($article['title']); ?></h3>
              <p><?php echo nl2br(htmlspecialchars($article['content'])); ?></p>
              <hr>
            </div>
          <?php else: ?>
            <p>Article not found.</p>
          <?php endif; ?>
        </div>

        <!-- Comments -->
        <div class="escp_content1">
          <h5>Comments</h5>
          <?php if ($comments->num_rows > 0): ?>
            <?php while($row = $comments->fetch_assoc()): ?>
              <div class="comment mb-3">
                <div class="small text-muted">
                  <?php echo htmlspecialchars($row['username']); ?> (<?php echo $row['profile_type']; ?>) 
                  — <?php echo date("M d, Y H:i", strtotime($row['created_at'])); ?>
                </div>
                <p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p>No comments yet.</p>
          <?php endif; ?>
        </div>

        <!-- Add Comment -->
        <?php if ($role === 'Coach' || $role === 'Player'): ?>
          <div class="escp_content1 mt-4">
            <div class="row">
              <div class="col-lg-12">
                <div class="escp_form">
                  <div class="container my-5">
                    <div class="row justify-content-center">
                      <div class="col-lg-8">
                        <h5 class="mb-3">Add a Comment</h5>
                        <?php if (!empty($error)): ?>
                          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                          <div class="mb-3">
                            <label for="comment" class="form-label">Your Comment</label>
                            <textarea name="comment" id="comment" class="form-control" rows="4" required></textarea>
                          </div>
                          <button type="submit" class="main-button">
                            <i class="fa fa-comment"></i> Post Comment
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
