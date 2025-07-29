<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PDO Database Test<br>";

try {
    // Connect using PDO
    $pdo = new PDO(
        'mysql:host=localhost;dbname=qr_attendance_db;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connected successfully using PDO<br>";
    
    // List tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if ($tables) {
        echo "<h3>Tables:</h3>";
        foreach ($tables as $table) {
            echo "- " . $table . "<br>";
        }
    } else {
        echo "No tables found";
    }
    
    // Check if attendance_grades table exists
    if (in_array('attendance_grades', $tables)) {
        echo "<h3>attendance_grades table exists</h3>";
        
        // Count grades
        $count = $pdo->query("SELECT COUNT(*) FROM attendance_grades")->fetchColumn();
        echo "Total grades: " . $count . "<br>";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE attendance_grades");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Table structure:</h4>";
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        }
    } else {
        echo "<h3>attendance_grades table does not exist</h3>";
        
        // Create the table
        $createTable = "CREATE TABLE IF NOT EXISTS attendance_grades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            term VARCHAR(50) NOT NULL,
            section VARCHAR(10) NOT NULL,
            attendance_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            attendance_grade DECIMAL(3,2) NOT NULL DEFAULT 5.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($createTable);
        echo "attendance_grades table created!<br>";
    }
    
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage();
}
?> 