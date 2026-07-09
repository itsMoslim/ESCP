<?php
include 'db_connect.php';

$sql = file_get_contents("tables.sql");

if ($conn->multi_query($sql)) {
    echo "All tables created successfully.<br>";
} else {
    echo "Error creating tables: " . $conn->error;
}

$conn->close();
?>
