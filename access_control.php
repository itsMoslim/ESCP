<?php

function checkAccess($requiredRole) {
    // If no session → guest user
    if (!isset($_SESSION['role'])) {
        header("Location: login.php");
        exit();
    }

    // If role mismatch → redirect to correct dashboard
    if ($_SESSION['role'] !== $requiredRole) {
        switch ($_SESSION['role']) {
            case 'Coach':
                header("Location: c_dashboard.php");
                break;
            case 'Player':
                header("Location: p_dashboard.php");
                break;
            case 'Admin':
                header("Location: a_dashboard.php");
                break;
            default:
                header("Location: login.php");
        }
        exit();
    }

    // Optional: enforce session timeout
    $timeout = 1200; // 20 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
    $_SESSION['last_activity'] = time();
}
?>
