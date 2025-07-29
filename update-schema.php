<?php
// Include database connection file
include('./conn/db_connect.php');

try {
    // Check if status column already exists
    $checkSQL = "SHOW COLUMNS FROM tbl_attendance LIKE 'status'";
    $checkResult = $conn_qr->query($checkSQL);
    
    if ($checkResult->num_rows === 0) {
        // Add status column if it doesn't exist
        $sql = "ALTER TABLE tbl_attendance ADD COLUMN status VARCHAR(20) AFTER time_in";
        
        if ($conn_qr->query($sql) === TRUE) {
            echo "Status column added successfully to tbl_attendance table.<br>";
            
            // Update existing records based on class time (default 8:00 AM)
            $defaultClassTime = '08:00:00';
            $updateSQL = "UPDATE tbl_attendance SET status = 
                            CASE 
                                WHEN TIME(time_in) <= '$defaultClassTime' THEN 'On Time' 
                                ELSE 'Late' 
                            END 
                          WHERE status IS NULL";
                          
            if ($conn_qr->query($updateSQL) === TRUE) {
                echo "Existing records updated with status values.";
            } else {
                echo "Error updating existing records: " . $conn_qr->error;
            }
        } else {
            echo "Error adding status column: " . $conn_qr->error;
        }
    } else {
        echo "Status column already exists in tbl_attendance table.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>