<?php
include 'header.php';
require_once 'db_connect.php';

$error = $success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("INSERT INTO support_requests (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $success = "Your message has been sent successfully!";
        } else {
            $error = "Error saving your request. Please try again.";
        }
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
                <h6><em>Get reliable help, trusted support, and dedicated assistance.</em></h6>
                <h4>GET IN TOUCH</h4>
                <div class="line"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="escp_content1">
          <div class="row">
            <div class="col-lg-12">

              <!-- Feedback Messages -->
              <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
              <?php endif; ?>
              <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
              <?php endif; ?>

              <!-- Contact Form -->
              <div class="escp_form">
                <div class="container my-5">
                  <div class="row justify-content-center">
                    <div class="col-lg-8">
                      <form action="" method="POST">
                        <div class="mb-3">
                          <label for="name" class="form-label">Your Name</label>
                          <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" required>
                        </div>
                        <div class="mb-3">
                          <label for="email" class="form-label">Your Email</label>
                          <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                          <label for="subject" class="form-label">Subject</label>
                          <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter subject" required>
                        </div>
                        <div class="mb-3">
                          <label for="message" class="form-label">Message</label>
                          <textarea class="form-control" id="message" name="message" rows="5" placeholder="Write your message" required></textarea>
                        </div>
                        <button type="submit" class="main-button">
                          <i class="fa fa-paper-plane"></i> Send Message
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
              <!-- End Contact Form -->

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
