<?php
session_start();

$_SESSION = [];

session_unset();
session_destroy();

session_regenerate_id(true);

header("Location: login.php?logout=success");
exit();
?>

