<?php
require_once '../includes/session_config.php';
require_once '../includes/auth_functions.php';
require_once 'database.php';

// Require admin access
requireRole('admin');

$user_school_id = $_SESSION['school_id'] ?? null;
$is_super_admin = hasRole('super_admin');

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_rooms':
            $school_id = $is_super_admin ? (int)$_POST['school_id'] : $user_school_id;
            
            $sql = "SELECT * FROM rooms WHERE school_id = ? AND status = 'available' ORDER BY room_name";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $school_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $rooms = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $rooms[] = $row;
            }
            
            echo json_encode(['success' => true, 'rooms' => $rooms]);
            exit();
            
        case 'check_conflicts':
            $school_id = $is_super_admin ? (int)$_POST['school_id'] : $user_school_id;
            $room = $_POST['room'];
            $day = $_POST['day'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            
            $conflicts = checkScheduleConflicts($conn, $school_id, $room, $day, $start_time, $end_time);
            
            echo json_encode(['success' => true, 'conflicts' => $conflicts]);
            exit();
            
        case 'generate_schedule':
            $school_id = $is_super_admin ? (int)$_POST['school_id'] : $user_school_id;
            $class_name = trim($_POST['class_name']);
            $instructor = trim($_POST['instructor']);
            $room = $_POST['room'];
            $day = $_POST['day'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            
            // Validate inputs
            if (empty($class_name) || empty($instructor) || empty($room) || empty($day) || empty($start_time) || empty($end_time)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit();
            }
            
            // Check for conflicts
            $conflicts = checkScheduleConflicts($conn, $school_id, $room, $day, $start_time, $end_time);
            if (!empty($conflicts)) {
                echo json_encode(['success' => false, 'message' => 'Schedule conflict detected', 'conflicts' => $conflicts]);
                exit();
            }
            
            // Insert schedule
            $sql = "INSERT INTO schedules (school_id, class_name, instructor, room, day_of_week, start_time, end_time, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            $created_by = $_SESSION['user_id'] ?? null;
            mysqli_stmt_bind_param($stmt, "issssssi", $school_id, $class_name, $instructor, $room, $day, $start_time, $end_time, $created_by);
            
            if (mysqli_stmt_execute($stmt)) {
                logActivity($conn, 'SCHEDULE_CREATED', "Class: {$class_name}, Room: {$room}, Day: {$day}");
                echo json_encode(['success' => true, 'message' => 'Schedule created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create schedule']);
            }
            exit();
            
        case 'auto_generate':
            $school_id = $is_super_admin ? (int)$_POST['school_id'] : $user_school_id;
            $classes = json_decode($_POST['classes'], true);
            
            $results = autoGenerateSchedules($conn, $school_id, $classes);
            
            echo json_encode($results);
            exit();
    }
}

function checkScheduleConflicts($conn, $school_id, $room, $day, $start_time, $end_time) {
    $sql = "SELECT * FROM schedules 
            WHERE school_id = ? AND room = ? AND day_of_week = ? AND status = 'active'
            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issssssss", $school_id, $room, $day, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $conflicts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $conflicts[] = $row;
    }
    
    return $conflicts;
}

function autoGenerateSchedules($conn, $school_id, $classes) {
    $generated = [];
    $failed = [];
    
    // Get available rooms
    $rooms_sql = "SELECT room_name FROM rooms WHERE school_id = ? AND status = 'available' ORDER BY room_name";
    $stmt = mysqli_prepare($conn, $rooms_sql);
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $rooms_result = mysqli_stmt_get_result($stmt);
    
    $available_rooms = [];
    while ($row = mysqli_fetch_assoc($rooms_result)) {
        $available_rooms[] = $row['room_name'];
    }
    
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $time_slots = [
        ['08:00:00', '09:30:00'],
        ['09:45:00', '11:15:00'],
        ['11:30:00', '13:00:00'],
        ['14:00:00', '15:30:00'],
        ['15:45:00', '17:15:00']
    ];
    
    foreach ($classes as $class) {
        $scheduled = false;
        
        foreach ($days as $day) {
            if ($scheduled) break;
            
            foreach ($time_slots as $slot) {
                if ($scheduled) break;
                
                foreach ($available_rooms as $room) {
                    $conflicts = checkScheduleConflicts($conn, $school_id, $room, $day, $slot[0], $slot[1]);
                    
                    if (empty($conflicts)) {
                        // Schedule the class
                        $sql = "INSERT INTO schedules (school_id, class_name, instructor, room, day_of_week, start_time, end_time, created_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        $created_by = $_SESSION['user_id'] ?? null;
                        mysqli_stmt_bind_param($stmt, "issssssi", $school_id, $class['name'], $class['instructor'], $room, $day, $slot[0], $slot[1], $created_by);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $generated[] = [
                                'class' => $class['name'],
                                'instructor' => $class['instructor'],
                                'room' => $room,
                                'day' => $day,
                                'time' => $slot[0] . ' - ' . $slot[1]
                            ];
                            $scheduled = true;
                            break;
                        }
                    }
                }
            }
        }
        
        if (!$scheduled) {
            $failed[] = $class;
        }
    }
    
    if (!empty($generated)) {
        logActivity($conn, 'AUTO_SCHEDULE_GENERATED', count($generated) . ' schedules created');
    }
    
    return [
        'success' => true,
        'generated' => $generated,
        'failed' => $failed,
        'message' => count($generated) . ' schedules generated, ' . count($failed) . ' failed'
    ];
}

// Get existing schedules
$schedules_sql = $is_super_admin 
    ? "SELECT s.*, sc.name as school_name FROM schedules s 
       LEFT JOIN schools sc ON s.school_id = sc.id 
       WHERE s.status = 'active' 
       ORDER BY s.day_of_week, s.start_time"
    : "SELECT s.*, sc.name as school_name FROM schedules s 
       LEFT JOIN schools sc ON s.school_id = sc.id 
       WHERE s.school_id = ? AND s.status = 'active' 
       ORDER BY s.day_of_week, s.start_time";

if ($is_super_admin) {
    $schedules_result = mysqli_query($conn, $schedules_sql);
} else {
    $stmt = mysqli_prepare($conn, $schedules_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_school_id);
    mysqli_stmt_execute($stmt);
    $schedules_result = mysqli_stmt_get_result($stmt);
}

$schedules = [];
while ($row = mysqli_fetch_assoc($schedules_result)) {
    $schedules[] = $row;
}

// Get schools for super admin
$schools = [];
if ($is_super_admin) {
    $schools = getAllSchools($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Generator - QR Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #098744;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), #0a5c2e);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: none;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: #0a5c2e;
            border-color: #0a5c2e;
        }
        
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .schedule-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .day-header {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .time-slot {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .conflict-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 10px;
            margin: 10px 0;
            color: #856404;
        }
        
        .auto-generate-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .class-input-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .class-input-row input {
            flex: 1;
        }
        
        .remove-class-btn {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-calendar-alt"></i> Schedule Generator</h1>
                    <p class="mb-0">Create and manage class schedules automatically</p>
                </div>
                <a href="admin_panel.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Admin Panel
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Manual Schedule Creation -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus"></i> Create Schedule Manually</h5>
            </div>
            <div class="card-body">
                <form id="manual_schedule_form">
                    <div class="row">
                        <?php if ($is_super_admin): ?>
                        <div class="col-md-3">
                            <label class="form-label">School</label>
                            <select class="form-select" id="manual_school_id" required>
                                <option value="">Select School</option>
                                <?php foreach ($schools as $school): ?>
                                <option value="<?php echo $school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" id="manual_school_id" value="<?php echo $user_school_id; ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-3">
                            <label class="form-label">Class Name</label>
                            <input type="text" class="form-control" id="class_name" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Instructor</label>
                            <input type="text" class="form-control" id="instructor" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Room</label>
                            <select class="form-select" id="room" required>
                                <option value="">Select Room</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label">Day</label>
                            <select class="form-select" id="day" required>
                                <option value="">Select Day</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="start_time" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">End Time</label>
                            <input type="time" class="form-control" id="end_time" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">Create Schedule</button>
                        </div>
                    </div>
                </form>
                
                <div id="conflict_warning" class="conflict-warning" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Conflict Detected:</strong>
                    <div id="conflict_details"></div>
                </div>
            </div>
        </div>

        <!-- Auto Generate Section -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-magic"></i> Auto Generate Schedules</h5>
            </div>
            <div class="card-body">
                <div class="auto-generate-section">
                    <form id="auto_generate_form">
                        <?php if ($is_super_admin): ?>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">School</label>
                                <select class="form-select" id="auto_school_id" required>
                                    <option value="">Select School</option>
                                    <?php foreach ($schools as $school): ?>
                                    <option value="<?php echo $school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php else: ?>
                        <input type="hidden" id="auto_school_id" value="<?php echo $user_school_id; ?>">
                        <?php endif; ?>
                        
                        <h6>Classes to Schedule:</h6>
                        <div id="classes_container">
                            <div class="class-input-row">
                                <input type="text" class="form-control" placeholder="Class Name" name="class_name[]" required>
                                <input type="text" class="form-control" placeholder="Instructor" name="instructor[]" required>
                                <button type="button" class="remove-class-btn" onclick="removeClassRow(this)" style="display: none;">Remove</button>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addClassRow()">
                            <i class="fas fa-plus"></i> Add Another Class
                        </button>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-magic"></i> Auto Generate Schedules
                            </button>
                        </div>
                    </form>
                </div>
                
                <div id="auto_generate_results" style="display: none;">
                    <h6>Generation Results:</h6>
                    <div id="results_content"></div>
                </div>
            </div>
        </div>

        <!-- Existing Schedules -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Current Schedules</h5>
            </div>
            <div class="card-body">
                <?php if (empty($schedules)): ?>
                <p class="text-muted text-center">No schedules created yet.</p>
                <?php else: ?>
                <div class="schedule-grid">
                    <?php
                    $grouped_schedules = [];
                    foreach ($schedules as $schedule) {
                        $grouped_schedules[$schedule['day_of_week']][] = $schedule;
                    }
                    
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    foreach ($days as $day):
                        if (isset($grouped_schedules[$day])):
                    ?>
                    <div class="schedule-item">
                        <div class="day-header"><?php echo $day; ?></div>
                        <?php foreach ($grouped_schedules[$day] as $schedule): ?>
                        <div class="time-slot">
                            <strong><?php echo htmlspecialchars($schedule['class_name']); ?></strong><br>
                            <small>
                                <?php echo date('g:i A', strtotime($schedule['start_time'])); ?> - 
                                <?php echo date('g:i A', strtotime($schedule['end_time'])); ?><br>
                                Instructor: <?php echo htmlspecialchars($schedule['instructor']); ?><br>
                                Room: <?php echo htmlspecialchars($schedule['room']); ?>
                                <?php if ($is_super_admin): ?>
                                <br>School: <?php echo htmlspecialchars($schedule['school_name']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load rooms when school is selected
        function loadRooms(schoolId, targetSelect) {
            if (!schoolId) return;
            
            fetch('schedule_generator.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_rooms&school_id=${schoolId}`
            })
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById(targetSelect);
                select.innerHTML = '<option value="">Select Room</option>';
                
                if (data.success) {
                    data.rooms.forEach(room => {
                        select.innerHTML += `<option value="${room.room_name}">${room.room_name} (${room.capacity} capacity)</option>`;
                    });
                }
            });
        }

        // School selection handlers
        <?php if ($is_super_admin): ?>
        document.getElementById('manual_school_id').addEventListener('change', function() {
            loadRooms(this.value, 'room');
        });
        <?php else: ?>
        // Auto-load rooms for single school
        loadRooms(<?php echo $user_school_id; ?>, 'room');
        <?php endif; ?>

        // Check for conflicts when time changes
        function checkConflicts() {
            const schoolId = document.getElementById('manual_school_id').value;
            const room = document.getElementById('room').value;
            const day = document.getElementById('day').value;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (!schoolId || !room || !day || !startTime || !endTime) return;
            
            fetch('schedule_generator.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=check_conflicts&school_id=${schoolId}&room=${room}&day=${day}&start_time=${startTime}&end_time=${endTime}`
            })
            .then(response => response.json())
            .then(data => {
                const warningDiv = document.getElementById('conflict_warning');
                const detailsDiv = document.getElementById('conflict_details');
                
                if (data.success && data.conflicts.length > 0) {
                    let conflictText = '';
                    data.conflicts.forEach(conflict => {
                        conflictText += `${conflict.class_name} (${conflict.start_time} - ${conflict.end_time})<br>`;
                    });
                    detailsDiv.innerHTML = conflictText;
                    warningDiv.style.display = 'block';
                } else {
                    warningDiv.style.display = 'none';
                }
            });
        }

        // Add event listeners for conflict checking
        ['room', 'day', 'start_time', 'end_time'].forEach(id => {
            document.getElementById(id).addEventListener('change', checkConflicts);
        });

        // Manual schedule form
        document.getElementById('manual_schedule_form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'generate_schedule');
            formData.append('school_id', document.getElementById('manual_school_id').value);
            formData.append('class_name', document.getElementById('class_name').value);
            formData.append('instructor', document.getElementById('instructor').value);
            formData.append('room', document.getElementById('room').value);
            formData.append('day', document.getElementById('day').value);
            formData.append('start_time', document.getElementById('start_time').value);
            formData.append('end_time', document.getElementById('end_time').value);
            
            fetch('schedule_generator.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        // Auto generate classes management
        function addClassRow() {
            const container = document.getElementById('classes_container');
            const newRow = document.createElement('div');
            newRow.className = 'class-input-row';
            newRow.innerHTML = `
                <input type="text" class="form-control" placeholder="Class Name" name="class_name[]" required>
                <input type="text" class="form-control" placeholder="Instructor" name="instructor[]" required>
                <button type="button" class="remove-class-btn" onclick="removeClassRow(this)">Remove</button>
            `;
            container.appendChild(newRow);
            
            // Show remove buttons
            document.querySelectorAll('.remove-class-btn').forEach(btn => {
                btn.style.display = 'block';
            });
        }

        function removeClassRow(button) {
            button.parentElement.remove();
            
            // Hide remove button if only one row left
            const rows = document.querySelectorAll('.class-input-row');
            if (rows.length === 1) {
                rows[0].querySelector('.remove-class-btn').style.display = 'none';
            }
        }

        // Auto generate form
        document.getElementById('auto_generate_form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const classNames = Array.from(document.querySelectorAll('input[name="class_name[]"]')).map(input => input.value);
            const instructors = Array.from(document.querySelectorAll('input[name="instructor[]"]')).map(input => input.value);
            
            const classes = classNames.map((name, index) => ({
                name: name,
                instructor: instructors[index]
            }));
            
            const formData = new FormData();
            formData.append('action', 'auto_generate');
            formData.append('school_id', document.getElementById('auto_school_id').value);
            formData.append('classes', JSON.stringify(classes));
            
            fetch('schedule_generator.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('auto_generate_results');
                const contentDiv = document.getElementById('results_content');
                
                if (data.success) {
                    let html = `<div class="alert alert-success">${data.message}</div>`;
                    
                    if (data.generated.length > 0) {
                        html += '<h6>Successfully Generated:</h6><ul>';
                        data.generated.forEach(schedule => {
                            html += `<li>${schedule.class} - ${schedule.instructor} (${schedule.room}, ${schedule.day} ${schedule.time})</li>`;
                        });
                        html += '</ul>';
                    }
                    
                    if (data.failed.length > 0) {
                        html += '<h6>Failed to Schedule:</h6><ul>';
                        data.failed.forEach(cls => {
                            html += `<li>${cls.name} - ${cls.instructor}</li>`;
                        });
                        html += '</ul>';
                    }
                    
                    contentDiv.innerHTML = html;
                    resultsDiv.style.display = 'block';
                    
                    if (data.generated.length > 0) {
                        setTimeout(() => location.reload(), 3000);
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>