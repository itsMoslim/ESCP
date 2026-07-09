<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">

  <title>ESCP</title>

  <!-- Bootstrap core CSS -->
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">


  <!-- Additional CSS Files -->
  <link rel="stylesheet" href="assets/css/fontawesome.css">
  <link rel="stylesheet" href="assets/css/escp.css">
  <link rel="stylesheet" href="assets/css/owl.css">
  <link rel="stylesheet" href="assets/css/animate.css">
  <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css" />

</head>

<body>

  <!-- ***** Preloader Start ***** -->
  <div id="js-preloader" class="js-preloader">
    <div class="preloader-inner">
      <span class="dot"></span>
      <div class="dots">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </div>
  <!-- ***** Preloader End ***** -->

  <!-- ***** Header Area Start ***** -->
  <header class="header-area header-sticky">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <nav class="main-nav">
            <!-- ***** Logo Start ***** -->
            <a href="index.php" class="logo">
              <img src="assets/images/logo_l.png" alt="">
            </a>
            <!-- ***** Logo End ***** -->
            
            <!-- ***** Menu Start ***** -->
            <ul class="nav">
              <li><a href="index.php"><i class="fa fa-home"></i> Home</a></li>
              <li><a href="coaches.php"><i class="fa fa-users"></i> Coaches</a></li>
              <li><a href="community.php"><i class="fa fa-comments"></i> Community</a></li>
              <li><a href="faq.php"><i class="fa fa-question-circle"></i> FAQs</a></li>
              <li><a href="support.php"><i class="fa fa-life-ring"></i> Support</a></li>
              <?php if (isset($_SESSION['role'])): ?>
                <li><a href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>


                <?php if ($_SESSION['role'] === 'Coach'): ?>
                  <li><a href="c_dashboard.php">Dashboard
                      <img src="assets/images/profile-header.png" alt="">
                    </a></li>
                <?php elseif ($_SESSION['role'] === 'Player'): ?>
                  <li><a href="p_dashboard.php">Dashboard
                      <img src="assets/images/profile-header.png" alt="">
                    </a></li>
                <?php elseif ($_SESSION['role'] === 'Admin'): ?>
                  <li><a href="a_dashboard.php">Dashboard
                      <img src="assets/images/profile-header.png" alt="">
                    </a></li>
                <?php endif; ?>
              <?php else: ?>
                <li><a href="login.php">Login
                    <img src="assets/images/login-header.png" alt="">
                  </a></li>
              <?php endif; ?>
            </ul>
            <a class='menu-trigger'>
              <span>Menu</span>
            </a>
            <!-- ***** Menu End ***** -->
          </nav>
        </div>
      </div>
    </div>
  </header>
  <!-- ***** Header Area End ***** -->