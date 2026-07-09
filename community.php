<?php
include 'header.php';
require_once 'db_connect.php';

$role = $_SESSION['role'] ?? null;
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
                <h4>Latest Articles</h4>
              </div>
            </div>
          </div>
        </div>

        <!-- Articles -->
        <div class="escp_content1">
          <div class="row">
            <div class="col-lg-12" id="articleList">
              <!-- Articles will load here via AJAX -->
            </div>
          </div>

          <!-- Load More -->
          <div class="text-center">
            <div class="main-button">
              <a href="javascript:void(0)" id="loadMore">Load More</a>
            </div>
            <br><br>
          </div>

          <!-- Add New Post -->
          <?php if($role === 'Coach' || $role === 'Player'): ?>
            <div class="text-center mt-4">
              <div class="main-button">
                <a href="add_article.php">Add New Post</a>
              </div>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
let offset = 0;
const limit = 3;
let totalArticles = 0;

// Fetch total count once
fetch("get_articles.php?count=1")
  .then(res => res.text())
  .then(count => {
    totalArticles = parseInt(count);
    loadArticles();
  });

function loadArticles() {
  fetch("get_articles.php?offset=" + offset + "&limit=" + limit)
    .then(res => res.text())
    .then(html => {
      if (html.trim() !== "") {
        document.getElementById("articleList").insertAdjacentHTML("beforeend", html);
        offset += limit;

        // Hide button if we've reached or exceeded total
        if (offset >= totalArticles) {
          document.getElementById("loadMore").style.display = "none";
        }
      } else {
        document.getElementById("loadMore").style.display = "none";
      }
    });
}

// Load more on button click
document.getElementById("loadMore").addEventListener("click", loadArticles);
</script>
