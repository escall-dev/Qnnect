<?php
// calendar-schedule.php
// Fully integrated PHP calendar for dashboard with filtering, view switching, and edit/delete
require_once 'conn/db_connect_pdo.php';

// Use the correct PDO variable for qr_attendance_db
$pdo = $conn_qr_pdo;

// Fetch filter options
$grade_courses = $pdo->query("SELECT DISTINCT grade_course FROM master_schedule WHERE grade_course IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$section_strands = $pdo->query("SELECT DISTINCT section_strand FROM master_schedule WHERE section_strand IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

// Get filter from GET or default
$selected_grade = $_GET['grade_course'] ?? '';
$selected_section = $_GET['section_strand'] ?? '';
$view = $_GET['view'] ?? 'week'; // week, day, month
$selected_day = $_GET['day'] ?? date('l');
$selected_month = $_GET['month'] ?? date('Y-m');

// Build WHERE clause for filters
$where = [];
$params = [];
if ($selected_grade) {
    $where[] = "grade_course = ?";
    $params[] = $selected_grade;
}
if ($selected_section) {
    $where[] = "section_strand = ?";
    $params[] = $selected_section;
}
if ($view == 'day') {
    $where[] = "day_of_week = ?";
    $params[] = $selected_day;
}
if ($view == 'month') {
    $month_start = $selected_month . '-01';
    $month_end = date('Y-m-t', strtotime($month_start));
    $where[] = "created_at BETWEEN ? AND ?";
    $params[] = $month_start . ' 00:00:00';
    $params[] = $month_end . ' 23:59:59';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("SELECT * FROM master_schedule $where_sql ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
$stmt->execute($params);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
.calendar-table { border-collapse: collapse; width: 100%; }
.calendar-table th, .calendar-table td { border: 1px solid #ccc; padding: 6px; vertical-align: top; min-width: 120px; }
.calendar-table th { background: #f0f0f0; }
.sched-block { margin-bottom: 4px; padding: 4px; border-radius: 4px; background: #e3f2fd; }
.sched-block .title { font-weight: bold; }
.sched-block .meta { font-size: 0.9em; color: #333; }
.edit-btn, .delete-btn { margin: 2px 2px 2px 0; padding: 2px 8px; font-size: 0.9em; }
</style>
<form method="get" id="calendar-filter-form" style="margin-bottom:10px;">
    <label>Grade/Course:
        <select name="grade_course" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach ($grade_courses as $gc): ?>
                <option value="<?= htmlspecialchars($gc) ?>" <?= $selected_grade == $gc ? 'selected' : '' ?>><?= htmlspecialchars($gc) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Section/Strand:
        <select name="section_strand" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach ($section_strands as $ss): ?>
                <option value="<?= htmlspecialchars($ss) ?>" <?= $selected_section == $ss ? 'selected' : '' ?>><?= htmlspecialchars($ss) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>View:
        <select name="view" onchange="this.form.submit()">
            <option value="week" <?= $view == 'week' ? 'selected' : '' ?>>Weekly</option>
            <option value="day" <?= $view == 'day' ? 'selected' : '' ?>>Daily</option>
            <option value="month" <?= $view == 'month' ? 'selected' : '' ?>>Monthly</option>
        </select>
    </label>
    <?php if ($view == 'day'): ?>
        <label>Day:
            <select name="day" onchange="this.form.submit()">
                <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                    <option value="<?= $d ?>" <?= $selected_day == $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php elseif ($view == 'month'): ?>
        <label>Month:
            <input type="month" name="month" value="<?= htmlspecialchars($selected_month) ?>" onchange="this.form.submit()">
        </label>
    <?php endif; ?>
</form>
<?php if ($view == 'week'): ?>
    <?php
    $calendar = [];
    foreach ($schedules as $sched) {
        $calendar[$sched['day_of_week']][$sched['start_time']][] = $sched;
    }
    $time_slots = [];
    $start = strtotime('07:00');
    $end = strtotime('19:00');
    for ($t = $start; $t < $end; $t += 60*60) {
        $time_slots[] = date('H:i', $t);
    }
    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    ?>
    <table class="calendar-table">
        <tr>
            <th>Time</th>
            <?php foreach ($days as $day): ?>
                <th><?= htmlspecialchars($day) ?></th>
            <?php endforeach; ?>
        </tr>
        <?php foreach ($time_slots as $slot): ?>
            <tr>
                <td><?= date('g:i A', strtotime($slot)) ?> - <?= date('g:i A', strtotime($slot . ' +59 minutes')) ?></td>
                <?php foreach ($days as $day): ?>
                    <td>
                        <?php
                        if (!empty($calendar[$day][$slot])) {
                            foreach ($calendar[$day][$slot] as $sched) {
                                ?>
                                <div class="sched-block">
                                    <div class="title"><?= htmlspecialchars($sched['subject']) ?></div>
                                    <div class="meta">
                                        <?= htmlspecialchars($sched['grade_course']) ?> | <?= htmlspecialchars($sched['section_strand']) ?><br>
                                        <?= htmlspecialchars($sched['instructor']) ?><br>
                                        Room: <?= htmlspecialchars($sched['room']) ?><br>
                                        <?= date('g:i A', strtotime($sched['start_time'])) ?> - <?= date('g:i A', strtotime($sched['end_time'])) ?>
                                    </div>
                                    <button class="edit-btn" 
                                            data-id="<?= $sched['id'] ?>"
                                            data-subject="<?= htmlspecialchars($sched['subject']) ?>"
                                            data-section="<?= htmlspecialchars($sched['section'] ?? ($sched['section_strand'] ?? '')) ?>"
                                            data-day="<?= htmlspecialchars($day) ?>"
                                            data-room="<?= htmlspecialchars($sched['room']) ?>">
                                        Edit
                                    </button>
                                    <button class="delete-btn" data-id="<?= $sched['id'] ?>">Delete</button>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
<?php elseif ($view == 'day'): ?>
    <h4><?= htmlspecialchars($selected_day) ?> Schedule</h4>
    <?php foreach ($schedules as $sched): ?>
        <div class="sched-block">
            <div class="title"><?= htmlspecialchars($sched['subject']) ?></div>
            <div class="meta">
                <?= htmlspecialchars($sched['grade_course']) ?> | <?= htmlspecialchars($sched['section_strand']) ?><br>
                <?= htmlspecialchars($sched['instructor']) ?><br>
                Room: <?= htmlspecialchars($sched['room']) ?><br>
                <?= date('g:i A', strtotime($sched['start_time'])) ?> - <?= date('g:i A', strtotime($sched['end_time'])) ?>
            </div>
            <button class="edit-btn" 
                    data-id="<?= $sched['id'] ?>"
                    data-subject="<?= htmlspecialchars($sched['subject']) ?>"
                    data-section="<?= htmlspecialchars($sched['section'] ?? ($sched['section_strand'] ?? '')) ?>"
                    data-day="<?= htmlspecialchars($selected_day) ?>"
                    data-room="<?= htmlspecialchars($sched['room']) ?>">
                Edit
            </button>
            <button class="delete-btn" data-id="<?= $sched['id'] ?>">Delete</button>
        </div>
    <?php endforeach; ?>
<?php elseif ($view == 'month'): ?>
    <h4>Schedules for <?= date('F Y', strtotime($selected_month . '-01')) ?></h4>
    <table class="calendar-table">
        <tr>
            <th>Date</th>
            <th>Day</th>
            <th>Time</th>
            <th>Grade/Course</th>
            <th>Section/Strand</th>
            <th>Subject</th>
            <th>Instructor</th>
            <th>Room</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($schedules as $sched): ?>
            <tr>
                <td><?= date('Y-m-d', strtotime($sched['created_at'])) ?></td>
                <td><?= htmlspecialchars($sched['day_of_week']) ?></td>
                <td><?= date('g:i A', strtotime($sched['start_time'])) ?> - <?= date('g:i A', strtotime($sched['end_time'])) ?></td>
                <td><?= htmlspecialchars($sched['grade_course']) ?></td>
                <td><?= htmlspecialchars($sched['section_strand']) ?></td>
                <td><?= htmlspecialchars($sched['subject']) ?></td>
                <td><?= htmlspecialchars($sched['instructor']) ?></td>
                <td><?= htmlspecialchars($sched['room']) ?></td>
                <td>
                    <button class="edit-btn" 
                            data-id="<?= $sched['id'] ?>"
                            data-subject="<?= htmlspecialchars($sched['subject']) ?>"
                            data-section="<?= htmlspecialchars($sched['section'] ?? ($sched['section_strand'] ?? '')) ?>"
                            data-day="<?= htmlspecialchars($sched['day_of_week']) ?>"
                            data-room="<?= htmlspecialchars($sched['room']) ?>">
                        Edit
                    </button>
                    <button class="delete-btn" data-id="<?= $sched['id'] ?>">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.onclick = function() {
            if (confirm('Delete this schedule?')) {
                fetch('api/delete-schedule.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: this.dataset.id })
                }).then(res => res.json()).then(resp => {
                    if (resp.success) location.reload();
                    else alert('Delete failed');
                });
            }
        };
    });
    // Edit (simple prompt version, for demo)
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.id;
            const newStart = prompt('New start time (e.g., 08:00 AM)?', this.closest('.sched-block')?.querySelector('.meta')?.textContent?.match(/(\d{1,2}:\d{2}\s?[AP]M)/)?.[0] || '');
            const newEnd = prompt('New end time (e.g., 09:00 AM)?');
            if (newStart && newEnd) {
                const form = new URLSearchParams();
                form.append('action', 'update');
                form.append('schedule_id', id);
                form.append('subject', this.dataset.subject || '');
                form.append('section', this.dataset.section || '');
                form.append('day_of_week', this.dataset.day || '');
                form.append('start_time', newStart);
                form.append('end_time', newEnd);
                form.append('room', this.dataset.room || '');
                fetch('api/manage-schedule.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: form.toString()
                }).then(res => res.json()).then(resp => {
                    if (resp.success) location.reload();
                    else alert('Edit failed');
                });
            }
        };
    });
});
</script> 