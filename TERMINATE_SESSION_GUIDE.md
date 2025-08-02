# Multi-Tenant Features Guide

## Overview
All features in the system now support multi-tenant functionality, meaning they work across all school accounts while maintaining data isolation. Each school has access to the same features but with their own unique data and records.

### Features That Support Multi-Tenancy:
- ✅ **Class Time Settings** - Each school can set their own class times
- ✅ **Subject/Section Filtering** - Dropdowns show only data for the current school
- ✅ **Terminate Session** - Each school can terminate their own sessions
- ✅ **Schedule Management** - Each school manages their own schedules
- ✅ **Attendance Tracking** - Each school tracks their own attendance
- ✅ **Instructor Management** - Each school manages their own instructors

## Multi-Tenant Architecture

### Data Isolation
- Each school's data is isolated by `school_id`
- Users can only access data for their assigned school
- Session variables are user-specific but respect school boundaries
- Database queries filter by `school_id` automatically

### Session Management
- `$_SESSION['school_id']` determines which school's data to show
- `$_SESSION['user_id']` identifies the specific user
- All APIs respect the current user's school assignment

## Terminate Class Session Feature

### Overview
This feature allows instructors to terminate the current active class session and start a new one. This is useful when:
- A class session needs to end early
- Switching between different classes
- Starting a new session after a break
- Clearing session data for administrative purposes

## How to Use

### 1. Access the Terminate Button
- Navigate to the main attendance page (`index.php`)
- Look for the "Active Class Session" section in the Class Time Settings
- You'll see a red "Terminate Session" button with a stop icon

### 2. Terminate the Session
- Click the "Terminate Session" button
- A confirmation dialog will appear asking if you're sure
- Click "OK" to proceed or "Cancel" to abort

### 3. What Happens When You Terminate
- The current class session is immediately ended
- All session variables are cleared:
  - `class_start_time`
  - `class_start_time_formatted`
  - `current_instructor_id`
  - `current_instructor_name`
  - `current_subject_id`
  - `current_subject_name`
  - `attendance_session_id`
  - `attendance_session_start`
  - `attendance_session_end`

- Any active attendance sessions in the database are marked as "terminated"
- The UI updates to show "No Active Session"
- You can now set a new class time for the next session

## Technical Details

### API Endpoint
- **URL**: `api/terminate-class-session.php`
- **Method**: POST
- **Authentication**: Requires logged-in user session

### Response Format
```json
{
  "success": true,
  "message": "Class session terminated successfully",
  "data": {
    "terminated_at": "2024-01-15 14:30:00",
    "previous_class_time": "14:30",
    "previous_instructor": "John Doe",
    "previous_subject": "Web Development"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message here"
}
```

## UI Changes

### Before Termination
- Green "Active Class Session" alert
- Shows current instructor, subject, and time
- "Terminate Session" button visible

### After Termination
- Yellow "No Active Session" alert
- Terminate button disappears
- Clear indication that a new session can be started

## Security Features
- Confirmation dialog prevents accidental termination
- Only logged-in users can terminate sessions
- Session data is properly cleared
- Database sessions are properly marked as terminated

## Testing
You can test the functionality using:
- `test_terminate_session.php` - Test script to verify API functionality
- Main interface in `index.php` - Full UI testing

## Files Modified
1. `api/terminate-class-session.php` - New API endpoint
2. `index.php` - Added terminate button and JavaScript functionality
3. `test_terminate_session.php` - Test script
4. `TERMINATE_SESSION_GUIDE.md` - This documentation

## CSS Styling
The terminate button includes:
- Red color scheme (`btn-danger`)
- Hover effects with scaling
- Loading state with spinner
- Disabled state styling
- Smooth transitions 