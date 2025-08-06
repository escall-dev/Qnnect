<?php
include("conn/conn.php");
include("includes/session_config.php");

// Functions for testing
function testTableExists($conn, $tableName) {
    $query = "SHOW TABLES LIKE '$tableName'";
    $result = $conn->query($query);
    return $result->rowCount() > 0;
}

function testInsertCourse($conn, $courseName, $user_id, $school_id) {
    try {
        $stmt = $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (?, ?, ?)");
        $result = $stmt->execute([$courseName, $user_id, $school_id]);
        return [
            'success' => $result,
            'id' => $conn->lastInsertId(),
            'error' => null
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'id' => null,
            'error' => $e->getMessage()
        ];
    }
}

function testInsertSection($conn, $sectionName, $user_id, $school_id) {
    try {
        $stmt = $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id) VALUES (?, ?, ?)");
        $result = $stmt->execute([$sectionName, $user_id, $school_id]);
        return [
            'success' => $result,
            'id' => $conn->lastInsertId(),
            'error' => null
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'id' => null,
            'error' => $e->getMessage()
        ];
    }
}

function testGetCourses($conn, $user_id, $school_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM tbl_courses WHERE user_id = ? OR user_id = 1");
        $stmt->execute([$user_id]);
        return [
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'error' => null
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'data' => [],
            'error' => $e->getMessage()
        ];
    }
}

function testGetSections($conn, $user_id, $school_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM tbl_sections WHERE user_id = ? OR user_id = 1");
        $stmt->execute([$user_id]);
        return [
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'error' => null
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'data' => [],
            'error' => $e->getMessage()
        ];
    }
}

function simulateAddStudent($conn, $courseName, $sectionName, $user_id, $school_id) {
    try {
        // First check if course exists
        $courseCheck = $conn->prepare("SELECT course_id FROM tbl_courses WHERE course_name = ? AND (user_id = ? OR user_id = 1)");
        $courseCheck->execute([$courseName, $user_id]);
        
        if ($courseCheck->rowCount() === 0) {
            // Insert course
            $insertCourse = $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (?, ?, ?)");
            $insertCourse->execute([$courseName, $user_id, $school_id]);
            $courseId = $conn->lastInsertId();
            echo "Inserted new course '$courseName' with ID: $courseId<br>";
        } else {
            $courseId = $courseCheck->fetchColumn();
            echo "Found existing course '$courseName' with ID: $courseId<br>";
        }
        
        // Then check if section exists
        $sectionCheck = $conn->prepare("SELECT section_id FROM tbl_sections WHERE section_name = ? AND (user_id = ? OR user_id = 1)");
        $sectionCheck->execute([$sectionName, $user_id]);
        
        if ($sectionCheck->rowCount() === 0) {
            // Insert section
            $insertSection = $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id) VALUES (?, ?, ?)");
            $insertSection->execute([$sectionName, $user_id, $school_id]);
            $sectionId = $conn->lastInsertId();
            echo "Inserted new section '$sectionName' with ID: $sectionId<br>";
        } else {
            $sectionId = $sectionCheck->fetchColumn();
            echo "Found existing section '$sectionName' with ID: $sectionId<br>";
        }
        
        return [
            'success' => true,
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'error' => null
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Start tests
echo "<h1>Course & Section Integration Test</h1>";

// 1. Check if tables exist
$user_id = $_SESSION['user_id'] ?? 1;
$school_id = $_SESSION['school_id'] ?? 1;

echo "<h3>1. Checking if tables exist:</h3>";
$coursesTableExists = testTableExists($conn, "tbl_courses");
$sectionsTableExists = testTableExists($conn, "tbl_sections");

echo "tbl_courses exists: " . ($coursesTableExists ? 'Yes' : 'No') . "<br>";
echo "tbl_sections exists: " . ($sectionsTableExists ? 'Yes' : 'No') . "<br>";

if (!$coursesTableExists || !$sectionsTableExists) {
    echo "<p>Tables don't exist. Please run setup-course-section-tables.php first.</p>";
    echo "<a href='setup-course-section-tables.php'>Run Setup Script</a>";
    exit;
}

// 2. Test inserting a new test course and section
echo "<h3>2. Test inserting course and section:</h3>";
$testCourseName = "TEST-COURSE-" . time();
$testSectionName = "TEST-SECTION-" . time();

$courseResult = testInsertCourse($conn, $testCourseName, $user_id, $school_id);
echo "Insert course result: " . ($courseResult['success'] ? 'Success' : 'Failed') . "<br>";
if (!$courseResult['success']) echo "Error: " . $courseResult['error'] . "<br>";

$sectionResult = testInsertSection($conn, $testSectionName, $user_id, $school_id);
echo "Insert section result: " . ($sectionResult['success'] ? 'Success' : 'Failed') . "<br>";
if (!$sectionResult['success']) echo "Error: " . $sectionResult['error'] . "<br>";

// 3. Test retrieving courses and sections
echo "<h3>3. Test retrieving courses and sections:</h3>";
$coursesResult = testGetCourses($conn, $user_id, $school_id);
$sectionsResult = testGetSections($conn, $user_id, $school_id);

echo "Get courses result: " . ($coursesResult['success'] ? 'Success' : 'Failed') . "<br>";
echo "Found " . count($coursesResult['data']) . " courses<br>";

echo "Get sections result: " . ($sectionsResult['success'] ? 'Success' : 'Failed') . "<br>";
echo "Found " . count($sectionsResult['data']) . " sections<br>";

// 4. Test simulating add student flow
echo "<h3>4. Test simulating add student flow:</h3>";
$simulateResult = simulateAddStudent($conn, "TEST-COURSE-" . uniqid(), "TEST-SECTION-" . uniqid(), $user_id, $school_id);

if ($simulateResult['success']) {
    echo "Simulation succeeded!<br>";
} else {
    echo "Simulation failed: " . $simulateResult['error'] . "<br>";
}

// 5. Display all courses and sections
echo "<h3>5. Current courses in database:</h3>";
$allCourses = $conn->query("SELECT * FROM tbl_courses ORDER BY course_name");

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Course Name</th><th>User ID</th><th>School ID</th></tr>";

while ($row = $allCourses->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['course_id'] . "</td>";
    echo "<td>" . $row['course_name'] . "</td>";
    echo "<td>" . $row['user_id'] . "</td>";
    echo "<td>" . $row['school_id'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>6. Current sections in database:</h3>";
$allSections = $conn->query("SELECT * FROM tbl_sections ORDER BY section_name");

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Section Name</th><th>User ID</th><th>School ID</th></tr>";

while ($row = $allSections->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['section_id'] . "</td>";
    echo "<td>" . $row['section_name'] . "</td>";
    echo "<td>" . $row['user_id'] . "</td>";
    echo "<td>" . $row['school_id'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Links:</h3>";
echo "<a href='masterlist.php'>Back to Masterlist</a> | ";
echo "<a href='fix_course_section_tables.php'>Fix Table Structure</a> | ";
echo "<a href='setup-course-section-tables.php'>Rebuild Tables</a>";
?>
