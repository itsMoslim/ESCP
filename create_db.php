<?php
$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "ESCP_DB";

// Create connection (without selecting DB yet)
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if database exists
$db_check = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");

if ($db_check->num_rows > 0) {
    echo "Database '$dbname' already exists.<br><br>";
} else {
    $sql = "CREATE DATABASE $dbname";
    if ($conn->query($sql) === TRUE) {
        echo "Database '$dbname' created successfully.<br><br><br>";
    } else {
        die("Error creating database: " . $conn->error);
    }
}

$conn->close();
?>
