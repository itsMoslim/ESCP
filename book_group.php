<?php
include 'header.php';
require_once 'db_connect.php';

// Helper function to render error page consistently
function renderError($message) {
    ?>
    <div class="container">
      <div class="page-content">
        <!-- Banner -->
        <div class="main-banner">
          <div class="header-text">
            <h6><em>Book Group Session</em></h6>
            <h4>Join Training</h4>
            <div class="line"></div>
          </div>
        </div>

        <!-- Error Message -->
        <div class="escp_form">
          <div class="container my-5">
            <div class="row justify-content-center">
              <div class="col-lg-8">
                <div class="alert alert-danger text-center">
                  <?php echo htmlspecialchars($message); ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    include 'footer.php';
    exit();
}

// Access control
if ($_SESSION['role'] !== 'Player') {
    renderError("Only players can book group sessions.");
}

$session_id = intval($_GET['session_id'] ?? 0);
$player_id = $_SESSION['profile_id'];

// Fetch group session details
$stmt = $conn->prepare("SELECT coach_profile_id, fee, max_participants FROM group_sessions WHERE session_id = ? AND status = 'Scheduled'");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$session = $result->fetch_assoc();

if (!$session) {
    renderError("Session not available for booking.");
}

$coach_id = $session['coach_profile_id'];
$amount = $session['fee'];
$max_participants = $session['max_participants'];

// Check current enrollment count
$countStmt = $conn->prepare("SELECT COUNT(*) AS enrolled FROM group_enrollments WHERE session_id = ?");
$countStmt->bind_param("i", $session_id);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$current_enrolled = $countResult['enrolled'];

// Check if player already enrolled
$checkStmt = $conn->prepare("SELECT enrollment_id FROM group_enrollments WHERE session_id = ? AND player_profile_id = ?");
$checkStmt->bind_param("ii", $session_id, $player_id);
$checkStmt->execute();
$alreadyEnrolled = $checkStmt->get_result()->num_rows > 0;

if ($alreadyEnrolled) {
    renderError("You are already enrolled in this session.");
}

if ($current_enrolled >= $max_participants) {
    renderError("This session has reached the maximum number of participants.");
}

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_number = $_POST['card_number'];
    $expiry = $_POST['expiry'];
    $cvv = $_POST['cvv'];

    // Insert payment record
    $payStmt = $conn->prepare("INSERT INTO payments (player_profile_id, amount, status, paid_at) VALUES (?, ?, 'Paid', NOW())");
    $payStmt->bind_param("id", $player_id, $amount);
    $payStmt->execute();
    $payment_id = $conn->insert_id;

    // Link payment to session
    $linkStmt = $conn->prepare("INSERT INTO payment_sessions (payment_id, session_type, session_id) VALUES (?, 'Group', ?)");
    $linkStmt->bind_param("ii", $payment_id, $session_id);
    $linkStmt->execute();

    // Enroll player
    $enrollStmt = $conn->prepare("INSERT INTO group_enrollments (session_id, player_profile_id, result) VALUES (?, ?, 'Pending')");
    $enrollStmt->bind_param("ii", $session_id, $player_id);
    $enrollStmt->execute();

    header("Location: p_coaching_sessions.php?booking=confirmed");
    exit();
}
?>


<div class="container">
  <div class="page-content">
    <!-- Banner -->
    <div class="main-banner">
      <div class="header-text">
        <h6><em>Book Group Session</em></h6>
        <h4>Join Training</h4>
        <div class="line"></div>
      </div>
    </div>

    <!-- Payment Form -->
    <div class="escp_form">
      <div class="container my-5">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <h5 class="mb-3">Payment Details</h5>
            <p>Session Fee: <strong><?php echo htmlspecialchars($amount); ?> SAR</strong></p>
            <form method="POST">
              <div class="mb-3">
                <label class="form-label">Card Number</label>
                <input type="text" name="card_number" class="form-control" maxlength="16" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Expiry Date</label>
                <input type="text" name="expiry" class="form-control" placeholder="MM/YY" required>
              </div>
              <div class="mb-3">
                <label class="form-label">CVV</label>
                <input type="text" name="cvv" class="form-control" maxlength="3" required>
              </div>
              <button type="submit" class="main-button">
                <i class="fa fa-credit-card"></i> Pay & Enroll
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
