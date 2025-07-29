<?php
require_once 'conn/db_connect_pdo.php';
$pdo = $conn_qr_pdo;
// Fetch dropdown data
$grade_courses = $pdo->query("SELECT DISTINCT grade_course FROM master_schedule WHERE grade_course IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$section_strands = $pdo->query("SELECT DISTINCT section_strand FROM master_schedule WHERE section_strand IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT DISTINCT subject FROM master_schedule WHERE subject IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$instructors = $pdo->query("SELECT DISTINCT instructor FROM master_schedule WHERE instructor IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
// Fetch rooms from login_register DB
require_once 'conn/db_connect_pdo.php';
$conn_login = $conn_login_pdo;
$rooms = $conn_login->query("SELECT room_name FROM rooms WHERE status='available'")->fetchAll(PDO::FETCH_COLUMN);
?>
<style>
#auto-sched-form { background: #f9f9f9; border: 1px solid #ccc; padding: 16px; border-radius: 8px; margin-bottom: 20px; max-width: 500px; }
#auto-sched-form label { display: block; margin-top: 10px; }
#auto-sched-form select, #auto-sched-form input[type='time'] { width: 100%; padding: 4px; margin-top: 2px; }
#auto-sched-form .form-row { display: flex; gap: 10px; }
#auto-sched-form .form-row > div { flex: 1; }
#auto-sched-form .mode-toggle { margin-top: 10px; }
#auto-sched-form button { margin-top: 14px; width: 100%; }
#sched-success { color: green; margin-top: 10px; display: none; }
</style>
<form id="auto-sched-form">
    <h4>Auto Schedule Setup</h4>
    <label>Grade/Course:
        <select name="grade_course" required>
            <option value="">-- Select --</option>
            <?php foreach ($grade_courses as $gc): ?>
                <option value="<?= htmlspecialchars($gc) ?>"><?= htmlspecialchars($gc) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Section/Strand:
        <select name="section_strand" required>
            <option value="">-- Select --</option>
            <?php foreach ($section_strands as $ss): ?>
                <option value="<?= htmlspecialchars($ss) ?>"><?= htmlspecialchars($ss) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Subject:
        <select name="subject" required>
            <option value="">-- Select --</option>
            <?php foreach ($subjects as $sub): ?>
                <option value="<?= htmlspecialchars($sub) ?>"><?= htmlspecialchars($sub) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Instructor:
        <select name="instructor" required>
            <option value="">-- Select --</option>
            <?php foreach ($instructors as $inst): ?>
                <option value="<?= htmlspecialchars($inst) ?>"><?= htmlspecialchars($inst) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Room:
        <select name="room" required>
            <option value="">-- Select --</option>
            <?php foreach ($rooms as $room): ?>
                <option value="<?= htmlspecialchars($room) ?>"><?= htmlspecialchars($room) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Day of Week:
        <select name="day_of_week" required>
            <option value="">-- Select --</option>
            <?php foreach (["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"] as $d): ?>
                <option value="<?= $d ?>"><?= $d ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="mode-toggle">
        <label><input type="radio" name="mode" value="auto" checked> Auto</label>
        <label><input type="radio" name="mode" value="manual"> Manual</label>
    </div>
    <div class="form-row">
        <div>
            <label>Start Time:
                <input type="time" name="start_time" required>
            </label>
        </div>
        <div>
            <label>End Time:
                <input type="time" name="end_time" required>
            </label>
        </div>
    </div>
    <button type="submit">Add Schedule</button>
    <div id="sched-success">Schedule added successfully!</div>
</form>
<script>
document.getElementById('auto-sched-form').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form).entries());
    fetch('api/schedule-management.php?action=add', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(res => res.json()).then(resp => {
        if (resp.success) {
            document.getElementById('sched-success').style.display = 'block';
            form.reset();
            setTimeout(() => { document.getElementById('sched-success').style.display = 'none'; }, 2000);
            if (window.location.href.indexOf('calendar-schedule.php') === -1) {
                // If calendar is on the same page, reload to show new schedule
                window.location.reload();
            }
        } else {
            alert('Failed to add schedule.');
        }
    });
};
</script> 