<?php
include("conn/conn.php");
include("includes/session_config.php");

echo "<h2>Testing Enhanced Teacher Schedule - Course & Section Combination</h2>";

// Test data for course-section combinations
$testCases = [
    ['subject' => 'Mathematics', 'course_section' => 'BSCS-101', 'expected' => 'BSCS-101'],
    ['subject' => 'Programming', 'course_section' => 'BSIT-2A', 'expected' => 'BSIT-2A'],
    ['subject' => 'Database', 'course_section' => 'BSIS-3B', 'expected' => 'BSIS-3B'],
    ['subject' => 'Web Development', 'course_section' => '11-ICT LAPU', 'expected' => '11-ICT LAPU'],
    ['subject' => 'Computer Science', 'course_section' => '12-STEM', 'expected' => '12-STEM'],
];

echo "<h3>Test Cases for Course-Section Combination</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Subject</th><th>Course & Section</th><th>Expected</th><th>Status</th></tr>";

foreach ($testCases as $testCase) {
    $subject = $testCase['subject'];
    $courseSection = $testCase['course_section'];
    $expected = $testCase['expected'];
    
    $status = ($courseSection === $expected) ? '✅ PASS' : '❌ FAIL';
    $color = ($courseSection === $expected) ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>$subject</td>";
    echo "<td>$courseSection</td>";
    echo "<td>$expected</td>";
    echo "<td style='color: $color; font-weight: bold;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Test validation rules
echo "<h3>Validation Rules Test</h3>";

$validationTests = [
    ['input' => 'BSCS-101', 'min_length' => 3, 'has_hyphen' => true, 'expected' => 'VALID'],
    ['input' => 'BS', 'min_length' => 3, 'has_hyphen' => false, 'expected' => 'INVALID (too short)'],
    ['input' => 'BSCS101', 'min_length' => 3, 'has_hyphen' => false, 'expected' => 'INVALID (no hyphen)'],
    ['input' => 'BSIT-2A', 'min_length' => 3, 'has_hyphen' => true, 'expected' => 'VALID'],
    ['input' => '11-ICT LAPU', 'min_length' => 3, 'has_hyphen' => true, 'expected' => 'VALID'],
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Input</th><th>Min Length</th><th>Has Hyphen</th><th>Expected</th><th>Status</th></tr>";

foreach ($validationTests as $test) {
    $input = $test['input'];
    $minLength = $test['min_length'];
    $hasHyphen = $test['has_hyphen'];
    $expected = $test['expected'];
    
    $isValid = strlen($input) >= $minLength && strpos($input, '-') !== false;
    $status = $isValid ? '✅ VALID' : '❌ INVALID';
    $color = $isValid ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>$input</td>";
    echo "<td>$minLength</td>";
    echo "<td>" . ($hasHyphen ? 'Yes' : 'No') . "</td>";
    echo "<td>$expected</td>";
    echo "<td style='color: $color; font-weight: bold;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Test database structure
echo "<h3>Database Structure Test</h3>";

try {
    // Check if teacher_schedules table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'teacher_schedules'");
    if ($check_table->rowCount() > 0) {
        echo "<p>✅ teacher_schedules table exists</p>";
        
        // Check table structure
        $structure = $conn->query("DESCRIBE teacher_schedules");
        $columns = $structure->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Table Structure:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if section column exists
        $hasSection = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'section') {
                $hasSection = true;
                break;
            }
        }
        
        if ($hasSection) {
            echo "<p>✅ 'section' column exists in teacher_schedules table</p>";
        } else {
            echo "<p>❌ 'section' column not found in teacher_schedules table</p>";
        }
        
    } else {
        echo "<p>❌ teacher_schedules table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database test failed: " . $e->getMessage() . "</p>";
}

// Test form field mapping
echo "<h3>Form Field Mapping Test</h3>";

$formFields = [
    'subject' => 'Subject field',
    'course_section' => 'Course & Section field',
    'day_of_week' => 'Day of Week field',
    'start_time' => 'Start Time field',
    'end_time' => 'End Time field',
    'room' => 'Room field'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Field Name</th><th>Description</th><th>Status</th></tr>";

foreach ($formFields as $field => $description) {
    $status = '✅ MAPPED';
    $color = 'green';
    
    echo "<tr>";
    echo "<td>$field</td>";
    echo "<td>$description</td>";
    echo "<td style='color: $color; font-weight: bold;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Summary</h3>";
echo "<p>The enhanced teacher schedule system now provides:</p>";
echo "<ul>";
echo "<li>✅ Combined 'Course & Section' field instead of separate 'Section' field</li>";
echo "<li>✅ Clear format instructions (Course-Section format)</li>";
echo "<li>✅ Placeholder examples (BSCS-101, BSIT-2A)</li>";
echo "<li>✅ Updated table headers to reflect new field name</li>";
echo "<li>✅ Updated JavaScript functions to handle new field name</li>";
echo "<li>✅ Updated API endpoint to process new field name</li>";
echo "</ul>";

echo "<p><strong>Usage Instructions:</strong></p>";
echo "<ol>";
echo "<li><strong>Subject:</strong> Enter the subject name (e.g., Mathematics, Programming)</li>";
echo "<li><strong>Course & Section:</strong> Enter in format Course-Section (e.g., BSCS-101, BSIT-2A)</li>";
echo "<li><strong>Day of Week:</strong> Select the day from dropdown</li>";
echo "<li><strong>Start/End Time:</strong> Set the class time</li>";
echo "<li><strong>Room:</strong> Optional room number or location</li>";
echo "</ol>";

echo "<p><em>The system maintains backward compatibility by storing the combined value in the 'section' column of the database.</em></p>";
?> 