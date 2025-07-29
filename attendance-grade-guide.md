# Attendance Grade System Guide

## Overview

The Attendance Grade System extension calculates and displays student attendance grades on a 1.00-5.00 scale based on their class attendance. This system helps instructors track student participation and provides objective attendance metrics.

## Features

1. **Attendance Sessions**: When an instructor sets class time, the system automatically creates an attendance session.
2. **Attendance Tracking**: Student QR code scans are recorded against these sessions.
3. **Grade Calculation**: Grades are automatically calculated based on attendance rate.
4. **Grade Reports**: View detailed attendance grade reports for all students.

## Grade Scale

The attendance grade is calculated on a 1.00-5.00 scale, where 1.00 is the highest and 5.00 is the lowest:

- 100% attendance → 1.00 (Excellent)
- 95-99% attendance → 1.25 (Very Good)
- 90-94% attendance → 1.50 (Good)
- 85-89% attendance → 1.75 (Satisfactory)
- 80-84% attendance → 2.00 (Above Average)
- 75-79% attendance → 2.50 (Average)
- 70-74% attendance → 2.75 (Below Average)
- 65-69% attendance → 3.00 (Fair)
- 60-64% attendance → 4.00 (Poor)
- Below 60% attendance → 5.00 (Failed)

## How to Use

### For Instructors

1. **Setting Class Time**:
   - Navigate to the Home page.
   - Select an instructor and subject from the dropdown.
   - Set the class start time and duration.
   - Click "Set Time" to create an attendance session.

2. **Viewing Attendance Grades**:
   - Go to "Data Reports" → "Attendance Grades" in the sidebar.
   - Use the filters to view grades for specific courses, sections, or terms.
   - Export reports to CSV or Excel for record keeping.

### For Administrators

1. **System Setup**:
   - Run the `setup-attendance-grade.php` script to set up all necessary database tables.
   - Ensure all instructors and subjects are properly configured.

2. **Monitoring Attendance Rates**:
   - View attendance rates and grades for all students in the Attendance Grades report.
   - Identify students with low attendance for intervention.

## Technical Details

The system uses the following components:

1. **Database Tables**:
   - `attendance_sessions`: Stores class sessions with start and end times.
   - `attendance_logs`: Records student attendance for each session.
   - `attendance_grades`: Stores calculated grades for each student.

2. **Grade Calculation**:
   - `Attendance Rate = (Attended Meetings / Total Meetings) * 100%`
   - The rate is then mapped to a grade on the 1.00-5.00 scale.

3. **Integration Points**:
   - Class time setting integrates with attendance sessions.
   - QR code scanning integrates with attendance logs.
   - Data Reports menu includes the Attendance Grades page.

## Troubleshooting

- **Missing Grades**: If a student has no grade, they may not have attended any sessions.
- **Incorrect Grade**: Check the attendance logs to verify attendance records.
- **Session Creation Issues**: Ensure instructors and subjects are correctly selected before setting class time.

---

For additional support, contact your system administrator. 