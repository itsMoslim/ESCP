<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Admin");
require_once 'db_connect.php';

// Fetch support requests
$result = $conn->query("
    SELECT subject, email, message, created_at 
    FROM support_requests 
    ORDER BY created_at DESC
");
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
                <h6><em>Review and manage guest support messages.</em></h6>
                <h4>Guest Messages</h4>
                <div class="line"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="game-details">
          <div class="row">
            <div class="col-lg-12">
              <h2>Welcome <?php echo htmlspecialchars($_SESSION['role'] ?? 'Unknown'); ?></h2>
            </div>
            <div class="col-lg-12">
              <div class="content">
                <div class="row">
                  <!-- Left Column: Guest Messages -->
                  <div class="col-lg-6">
                    <div class="left-info">
                      <div class="left">
                        <h4>All Support Requests</h4>
                      </div>
                      <br><br><br>

                      <?php
                      if ($result && $result->num_rows > 0) {
                          while($row = $result->fetch_assoc()) {
                              echo "<h5>" . htmlspecialchars($row['subject']) . "</h5>";
                              echo "<h6>" . htmlspecialchars($row['email']) . "</h6>";
                              echo "<p>" . nl2br(htmlspecialchars($row['message'])) . "</p>";
                              echo "<p><small>Submitted on: " . $row['created_at'] . "</small></p>";
                              echo "<hr>";
                          }
                      } else {
                          echo "<p>No support requests found.</p>";
                      }
                      ?>
                    </div>
                  </div>

                  <!-- Right Column: Control Hub -->
                  <div class="col-lg-6">
                    <div class="right-info">
                      <h4>Control Hub</h4>
                      <?php include 'a_menu.php'; ?>
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
