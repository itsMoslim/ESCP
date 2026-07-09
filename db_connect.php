<?php
$servername = "localhost";
$username = "root";   // adjust if needed
$password = "";
$dbname = "ESCP_DB";

// Create connection directly to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
