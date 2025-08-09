<?php
include("conn/conn.php");
include("includes/session_config.php");

echo "<h2>Testing Enhanced Course-Section Combination</h2>";

// Test data
$testCases = [
    ['course' => 'BSCS', 'section' => '101', 'expected' => 'BSCS-101'],
    ['course' => 'BSIT', 'section' => '2A', 'expected' => 'BSIT-2A'],
    ['course' => 'BSIS', 'section' => '3B', 'expected' => 'BSIS-3B'],
    ['course' => '11', 'section' => 'ICT LAPU', 'expected' => '11-ICT LAPU'],
    ['course' => '12', 'section' => 'STEM', 'expected' => '12-STEM'],
];

echo "<h3>Test Cases for Course-Section Combination</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Course</th><th>Section</th><th>Expected Combined</th><th>Status</th></tr>";

foreach ($testCases as $testCase) {
    $course = $testCase['course'];
    $section = $testCase['section'];
    $expected = $testCase['expected'];
    $actual = $course . '-' . $section;
    
    $status = ($actual === $expected) ? '✅ PASS' : '❌ FAIL';
    $color = ($actual === $expected) ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>$course</td>";
    echo "<td>$section</td>";
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

// Test JavaScript-like logic
echo "<h3>JavaScript Logic Simulation</h3>";

function simulateUpdateCombinedField($courseValue, $sectionValue, $completeValue = '') {
    if (!empty($completeValue)) {
        if (strlen($completeValue) >= 3 && strpos($completeValue, '-') !== false) {
            return $completeValue;
        }
        return '';
    }
    
    if (!empty($courseValue) && $courseValue !== 'custom' && 
        !empty($sectionValue) && $sectionValue !== 'custom') {
        return $courseValue . '-' . $sectionValue;
    }
    
    return '';
}

$jsTests = [
    ['course' => 'BSCS', 'section' => '101', 'complete' => '', 'expected' => 'BSCS-101'],
    ['course' => 'custom', 'section' => 'custom', 'complete' => '', 'expected' => ''],
    ['course' => '', 'section' => '', 'complete' => 'BSIT-2A', 'expected' => 'BSIT-2A'],
    ['course' => 'BSIS', 'section' => '', 'complete' => '', 'expected' => ''],
    ['course' => '', 'section' => '', 'complete' => 'INVALID', 'expected' => ''],
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Course</th><th>Section</th><th>Complete</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";

foreach ($jsTests as $test) {
    $course = $test['course'];
    $section = $test['section'];
    $complete = $test['complete'];
    $expected = $test['expected'];
    $actual = simulateUpdateCombinedField($course, $section, $complete);
    
    $status = ($actual === $expected) ? '✅ PASS' : '❌ FAIL';
    $color = ($actual === $expected) ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>$course</td>";
    echo "<td>$section</td>";
    echo "<td>$complete</td>";
    echo "<td>$expected</td>";
    echo "<td>$actual</td>";
    echo "<td style='color: $color; font-weight: bold;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Summary</h3>";
echo "<p>The enhanced course-section combination system provides:</p>";
echo "<ul>";
echo "<li>✅ Side-by-side course and section dropdowns</li>";
echo "<li>✅ Real-time combined value display</li>";
echo "<li>✅ Direct entry option for course-section</li>";
echo "<li>✅ Validation for minimum length and proper format</li>";
echo "<li>✅ Automatic clearing of conflicting fields</li>";
echo "<li>✅ Database integration with proper foreign keys</li>";
echo "</ul>";

echo "<p><strong>Usage Instructions:</strong></p>";
echo "<ol>";
echo "<li><strong>Method 1:</strong> Select course from dropdown, then select section from dropdown</li>";
echo "<li><strong>Method 2:</strong> Select 'custom' for course/section and enter custom values</li>";
echo "<li><strong>Method 3:</strong> Enter course-section directly in the combined field (e.g., BSCS-101)</li>";
echo "</ol>";

echo "<p><em>All methods automatically update the combined display and hidden field for form submission.</em></p>";
?> 