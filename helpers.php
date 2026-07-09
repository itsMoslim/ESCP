<?php


// Helper function to run queries with feedback
function createTable($conn, $sql, $tableName, &$createdCount, &$existingCount) {
    // Check if table exists in current database
    $check = $conn->query("SELECT TABLE_NAME 
                           FROM INFORMATION_SCHEMA.TABLES 
                           WHERE TABLE_SCHEMA = DATABASE() 
                           AND TABLE_NAME = '$tableName'");
    if ($check && $check->num_rows > 0) {
        echo "Table '$tableName' already exists.<br>";
        $existingCount++;
    } else {
        if ($conn->query($sql) === TRUE) {
            echo "Table '$tableName' created successfully.<br>";
            $createdCount++;
        } else {
            echo "Error creating table '$tableName': " . $conn->error . "<br>";
        }
    }
}

// Helper function for inserts with feedback
function insertData($conn, $sql, $tableName) {
    if ($conn->query($sql) === TRUE) {
        $rows = $conn->affected_rows;
        if ($rows > 0) {
            echo "$rows record(s) inserted into '$tableName'.<br>";
        } else {
            echo "No new records inserted into '$tableName' (all already existed).<br>";
        }
    } else {
        echo "Error inserting into '$tableName': " . $conn->error . "<br>";
    }
}
?>
