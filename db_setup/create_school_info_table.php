<?php
// Include database connection
include('../conn/db_connect.php');

// SQL to create the school_info table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS school_info (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    school_address TEXT,
    school_contact VARCHAR(50),
    school_email VARCHAR(100),
    school_website VARCHAR(255),
    school_logo_path VARCHAR(255),
    school_motto TEXT,
    school_vision TEXT,
    school_mission TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn_qr->query($sql) === TRUE) {
    echo "school_info table created successfully or already exists.<br>";
    
    // Check if there's any existing data
    $check_data = "SELECT COUNT(*) as count FROM school_info";
    $result = $conn_qr->query($check_data);
    $row = $result->fetch_assoc();
    
    // If no data exists, insert default values
    if ($row['count'] == 0) {
        $insert_default = "INSERT INTO school_info (
            school_name, 
            school_address, 
            school_contact, 
            school_email, 
            school_website, 
            school_logo_path,
            school_motto,
            school_vision,
            school_mission,
            created_at,
            updated_at
        ) VALUES (
            'School Name',
            'School Address',
            'Contact Number',
            'school@email.com',
            'www.schoolwebsite.com',
            '../admin/image/SPCPC-logo-trans.png',
            'School Motto',
            'School Vision',
            'School Mission',
            NOW(),
            NOW()
        )";
        
        if ($conn_qr->query($insert_default) === TRUE) {
            echo "Default school information inserted successfully.<br>";
        } else {
            echo "Error inserting default school information: " . $conn_qr->error . "<br>";
        }
    }
} else {
    echo "Error creating school_info table: " . $conn_qr->error . "<br>";
}

// Safely close the connection
if (isset($conn_qr) && $conn_qr instanceof mysqli) {
    try {
        if ($conn_qr->ping()) {
            $conn_qr->close();
        }
    } catch (Throwable $e) {
        // Connection is already closed or invalid, do nothing
    }
}

echo "Database setup completed.";
?> 