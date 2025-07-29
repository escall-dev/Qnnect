<?php
// Add Schedule Modal
?>
<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1" role="dialog" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addScheduleModalLabel">
                    <i class="fas fa-plus-circle"></i>
                    Add New Schedule
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <input type="hidden" name="add_schedule" value="1">
                    <div class="form-group">
                        <label for="instructor_name">
                            <i class="fas fa-user-tie"></i>
                            Instructor Name
                        </label>
                        <input type="text" class="form-control" id="instructor_name" name="instructor_name" required>
                    </div>
                    <div class="form-group">
                        <label for="room">
                            <i class="fas fa-door-open"></i>
                            Room
                        </label>
                        <input type="text" class="form-control" id="room" name="room" required>
                    </div>
                    <div class="form-group">
                        <label for="course_section">
                            <i class="fas fa-graduation-cap"></i>
                            Course/Section
                        </label>
                        <input type="text" class="form-control" id="course_section" name="course_section" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">
                            <i class="fas fa-book"></i>
                            Subject
                        </label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-calendar-day"></i>
                            Days
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php
                            $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                            foreach ($weekdays as $day) {
                                echo '<div class="form-check form-check-inline">';
                                echo '<input class="form-check-input" type="checkbox" name="days[]" id="' . strtolower($day) . '" value="' . $day . '">';
                                echo '<label class="form-check-label" for="' . strtolower($day) . '">' . $day . '</label>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="start_time">
                            <i class="fas fa-clock"></i>
                            Start Time
                        </label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">
                            <i class="fas fa-clock"></i>
                            End Time
                        </label>
                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-plus"></i>
                        Add Schedule
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editScheduleModalLabel">
                    <i class="fas fa-edit"></i>
                    Edit Schedule
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <input type="hidden" name="update_schedule" value="1">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    <div class="form-group">
                        <label for="edit_instructor_name">
                            <i class="fas fa-user-tie"></i>
                            Instructor Name
                        </label>
                        <input type="text" class="form-control" id="edit_instructor_name" name="instructor_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_room">
                            <i class="fas fa-door-open"></i>
                            Room
                        </label>
                        <input type="text" class="form-control" id="edit_room" name="room" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_course_section">
                            <i class="fas fa-graduation-cap"></i>
                            Course/Section
                        </label>
                        <input type="text" class="form-control" id="edit_course_section" name="course_section" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_subject">
                            <i class="fas fa-book"></i>
                            Subject
                        </label>
                        <input type="text" class="form-control" id="edit_subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-calendar-day"></i>
                            Days
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php
                            foreach ($weekdays as $day) {
                                echo '<div class="form-check form-check-inline">';
                                echo '<input class="form-check-input edit-day" type="checkbox" name="days[]" id="edit_' . strtolower($day) . '" value="' . $day . '">';
                                echo '<label class="form-check-label" for="edit_' . strtolower($day) . '">' . $day . '</label>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_start_time">
                            <i class="fas fa-clock"></i>
                            Start Time
                        </label>
                        <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_end_time">
                            <i class="fas fa-clock"></i>
                            End Time
                        </label>
                        <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.modal-header {
    background: linear-gradient(135deg, #0C713D, #0a5a31);
    color: white;
    border: none;
    border-radius: 12px 12px 0 0;
    padding: 15px 20px;
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.2rem;
}

.modal-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #333;
    font-weight: 500;
    margin-bottom: 8px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #ddd;
    padding: 8px 12px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #0C713D;
    box-shadow: 0 0 0 0.2rem rgba(12, 113, 61, 0.25);
}

.form-check-inline {
    margin-right: 15px;
}

.btn-block {
    padding: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.close {
    color: white;
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.close:hover {
    color: white;
    opacity: 1;
}
</style>

<script>
function editSchedule(schedule) {
    // Populate the edit form with schedule data
    document.getElementById('edit_schedule_id').value = schedule.id;
    document.getElementById('edit_instructor_name').value = schedule.instructor_name;
    document.getElementById('edit_room').value = schedule.room;
    document.getElementById('edit_course_section').value = schedule.course_section;
    document.getElementById('edit_subject').value = schedule.subject;
    document.getElementById('edit_start_time').value = schedule.start_time;
    document.getElementById('edit_end_time').value = schedule.end_time;
    
    // Reset all checkboxes
    document.querySelectorAll('.edit-day').forEach(checkbox => checkbox.checked = false);
    
    // Check the appropriate days
    const days = schedule.days_of_week.split(',');
    days.forEach(day => {
        const checkbox = document.getElementById('edit_' + day.toLowerCase());
        if (checkbox) checkbox.checked = true;
    });
    
    // Show the modal
    $('#editScheduleModal').modal('show');
}
</script> 