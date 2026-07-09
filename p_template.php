<?php
include 'header.php';
require_once "access_control.php";
checkAccess("Player");
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
                                <h6><em>Appropriate page headline</em></h6>
                                <h4>Page Title</h4>
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
                                    <div class="col-lg-6">
                                        <div class="left-info">
                                                <h4>My Profile</h4><br>
                                              
                                           <p>coming soon</p>

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