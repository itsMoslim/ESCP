<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ESCP_DB";

// Connect to MySQL server
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop old DB if exists
$conn->query("DROP DATABASE IF EXISTS $dbname");

// Create fresh DB
if ($conn->query("CREATE DATABASE $dbname") === TRUE) {
    echo "Database '$dbname' created successfully.<br>";
} else {
    echo "Error creating database: " . $conn->error;
}

$conn->close();
?>
