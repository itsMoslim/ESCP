<?php
include 'header.php';
require_once 'db_connect.php';

$role = $_SESSION['role'] ?? null;
$username = $_SESSION['username'] ?? null;
$profile_id = $_SESSION['profile_id'] ?? null;

// Only allow Coaches and Players
if ($role !== 'Coach' && $role !== 'Player') {
    echo "<div class='container'><div class='alert alert-danger mt-5'>Access denied. Only Coaches and Players can add articles.</div></div>";
    include 'footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $wordCount = str_word_count($content);

    if (empty($title) || empty($content)) {
        $error = "Please fill in all fields.";
    } elseif ($wordCount > 1000) {
        $error = "Article must be 1000 words or less.";
    } else {
        $stmt = $conn->prepare("INSERT INTO articles (user_id, title, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $profile_id, $title, $content);
        $stmt->execute();
        header("Location: community.php");
        exit();
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
                <h6><em>Community Blog</em></h6>
                <h4>Add New Article</h4>
                <div class="line"></div>
              </div>
            </div>
          </div>
        </div>

       ]
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
                          <label for="title" class="form-label">Article Title</label>
                          <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                          <label for="content" class="form-label">Article Content</label>
                          <textarea class="form-control" id="content" name="content" rows="8" required></textarea>
                          <small class="text-muted">Maximum 1000 words</small>
                        </div>
                        <button type="submit" class="main-button">
                          <i class="fa fa-plus"></i> Publish Article
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
