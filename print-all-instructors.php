<?php
// Include your database connection
include('./conn/conn.php');

// Set the timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Fetch all students
$printStmt = $conn->prepare("SELECT * FROM tbl_student ORDER BY tbl_student_id DESC");
$printStmt->execute();
$allRecords = $printStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Student List</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./styles/print-all-instructors.css">
 
</head>
<body onload="window.print()"> <!-- Automatically triggers print dialog -->

    <div class="container">
        <h4 class="text-center mb-4">Complete Students List <small>(Newest First)</small></h4>

        <table class="table table-bordered text-center table-sm" style="border-collapse: collapse; border: 2px solid black;">
        <thead style="background-color: #098744; color: black;">
                <tr>
                    <th scope="col" style="border: 2px solid black; color: black;">#</th>
                    <th scope="col" style="border: 2px solid black; color: black;">Name</th>
                    <th scope="col" style="border: 2px solid black; color: black;">Dept. & Subj.</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($allRecords)) {
                    echo "<tr><td colspan='3' class='text-center' style='border: 2px solid black;'>No records found.</td></tr>";
                } else {
                    foreach ($allRecords as $record) {
                        echo "<tr style='border: 2px solid black;'>";
                        echo "<td style='border: 2px solid black;'>" . htmlspecialchars($record['tbl_student_id']) . "</td>";
                        echo "<td style='border: 2px solid black;'>" . htmlspecialchars($record['student_name']) . "</td>";
                        echo "<td style='border: 2px solid black;'>" . htmlspecialchars($record['course_section']) . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <div style="position: fixed; bottom: 10px; right: 10px; font-size: 12px;">
        Printed on: <?php echo date('Y-m-d H:i:s'); ?>
    </div>

</body>
</html>
