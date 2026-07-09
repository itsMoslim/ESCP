<?php
include 'header.php';
include 'db_connect.php';

// Query to fetch verified coaches with their details
$sql = "SELECT 
            c.fullname AS coach_name,
            p.profile_id,
            p.rating,
            p.profile_picture AS picture,
            g.name AS game_name,
            COALESCE(gs.session_count,0) + COALESCE(ss.session_count,0) AS total_sessions
        FROM coaches c
        JOIN profiles p 
            ON c.username = p.username AND p.profile_type = 'Coach'
        LEFT JOIN (
            SELECT coach_profile_id, COUNT(*) AS session_count
            FROM group_sessions
            GROUP BY coach_profile_id
        ) gs ON p.profile_id = gs.coach_profile_id
        LEFT JOIN (
            SELECT coach_profile_id, COUNT(*) AS session_count
            FROM solo_sessions
            GROUP BY coach_profile_id
        ) ss ON p.profile_id = ss.coach_profile_id
        LEFT JOIN user_games ug 
            ON p.profile_id = ug.profile_id
        LEFT JOIN games g 
            ON ug.game_id = g.game_id
        WHERE c.verification_flag = TRUE 
          AND c.account_active = TRUE";

$result = $conn->query($sql);
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
                <h6><em>Browse verified coaches, explore their expertise, and book sessions with confidence.</em></h6>
                <h4>Coaches</h4>
                <div class="line"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Coaches Listing -->
        <div class="escp_content1">
          <div class="row">
            <div class="col-lg-12">
              <div class="heading-section">
                <h4><em>All Verified Coaches</em></h4>
              </div>
              <div class="row">

                <?php while ($row = $result->fetch_assoc()): ?>
                  <div class="col-lg-4">
                    <div class="item">
                      <img src="assets/pictures/<?php echo htmlspecialchars($row['picture'] ?? ''); ?>" alt="">
                      <?php $firstName = explode(' ', $row['coach_name'])[0]; ?>
                      <h4><?php echo htmlspecialchars($firstName); ?>
                        <span class="small">
                          <?php echo htmlspecialchars($row['game_name'] ?? ''); ?>
                        </span>
                      </h4>

                      <ul>
                        <li><i class="fa fa-star"></i> <?php echo htmlspecialchars($row['rating']); ?></li>
                        <li><i class="fa fa-calendar"></i> <?php echo (int)$row['total_sessions']; ?> sessions</li>
                      </ul>
                    </div>
                    <div class="col-lg-12">
                      <div class="main-button">
                        <a href="coachprofile.php?id=<?php echo (int)$row['profile_id']; ?>">Book Now</a>
                      </div><br><br><br><br>
                    </div>
                  </div>
                <?php endwhile; ?>
                <br><br>
                <div class="col-lg-12">
                  <div class="main-button">
                    <a href="personalizedcoach.php">Personalized Coach Selection</a>
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
