# Logout Class Time Termination Guide

## Overview

When a user logs out from the Qnnect system, the logout process now automatically terminates any active class time activation that was set from the `index.php` page. This ensures that class sessions are properly cleaned up and don't persist after the user logs out.

## What Gets Terminated

The logout process terminates the following class time related data:

### 1. Session Variables
- `$_SESSION['class_start_time']` - The active class start time
- `$_SESSION['class_start_time_formatted']` - Formatted class start time
- `$_SESSION['current_instructor_id']` - Current instructor ID
- `$_SESSION['current_instructor_name']` - Current instructor name
- `$_SESSION['current_subject_id']` - Current subject ID
- `$_SESSION['current_subject_name']` - Current subject name
- `$_SESSION['attendance_session_id']` - Active attendance session ID
- `$_SESSION['attendance_session_start']` - Attendance session start time
- `$_SESSION['attendance_session_end']` - Attendance session end time

### 2. Database Records

#### Class Time Settings (`class_time_settings` table)
- Clears `start_time` column (new format)
- Clears `class_start_time` column (legacy format)
- Updates `updated_at` timestamp
- Handles both column formats for backward compatibility

#### Teacher Schedules (`teacher_schedules` table)
- Sets `status` to 'inactive' for all active schedules
- Updates `updated_at` timestamp
- Ensures no active teacher schedules remain

#### Attendance Sessions (`attendance_sessions` table)
- Sets `end_time` to current timestamp
- Sets `status` to 'terminated'
- Terminates all active attendance sessions for the school

### 3. API Call
- Calls the `terminate-class-session.php` API for comprehensive cleanup
- Ensures all termination logic is executed

## Files Modified

### 1. `logout.php` (Main logout file)
- Enhanced with comprehensive termination logic
- Added detailed logging for debugging
- Calls terminate-class-session API
- Handles both new and legacy database structures

### 2. `admin/logout.php` (Admin logout file)
- Enhanced to match main logout functionality
- Added comprehensive termination logic
- Added detailed logging for debugging
- Calls terminate-class-session API

## Logging

The logout process logs all termination activities for debugging purposes:

```
[LOGOUT] Terminating class time activation for user: user@example.com, school_id: 1
[LOGOUT] Cleared class_time_settings.start_time for 1 row(s) (school_id: 1)
[LOGOUT] Set teacher_schedules status to 'inactive' for 2 row(s) (school_id: 1)
[LOGOUT] Successfully called terminate-class-session API
[LOGOUT] Class time activation termination completed for user: user@example.com, school_id: 1
```

## How It Works

### 1. User Clicks Logout
When a user clicks the logout link, the system starts the termination process.

### 2. Session Data Extraction
The system extracts user information (email, school_id, user_id) from the session before clearing it.

### 3. Database Cleanup
The system performs the following database operations:
- Clears class time settings
- Sets teacher schedules to inactive
- Terminates attendance sessions
- Updates timestamps

### 4. API Call
The system makes an HTTP request to the terminate-class-session API for additional cleanup.

### 5. Session Destruction
Finally, the system destroys the session and redirects to the login page.

## Testing

Use the `test_logout_termination.php` script to verify the termination functionality:

1. **Check Current State**: The script shows what's currently active
2. **Simulate Termination**: Shows what would be terminated
3. **Test Actual Logout**: Provides a button to test the actual logout process
4. **Check Logs**: Instructions for verifying the termination in logs

## Error Handling

The logout process is designed to be fail-safe:
- If database operations fail, the logout continues
- If API calls fail, the logout continues
- All errors are logged for debugging
- Session destruction always occurs

## Multi-School Support

The termination process respects the multi-school architecture:
- Uses `school_id` from the session
- Only terminates sessions for the specific school
- Maintains data isolation between schools

## Backward Compatibility

The system handles both new and legacy database structures:
- Supports both `start_time` and `class_start_time` columns
- Works with or without `school_id` columns
- Gracefully handles missing tables

## Security Considerations

- Session data is extracted before database operations
- All database operations use prepared statements
- School-specific termination prevents cross-school data access
- Session cookies are properly cleared

## Troubleshooting

### Common Issues

1. **Termination not working**: Check error logs for database connection issues
2. **Partial termination**: Verify all required tables exist
3. **Session persistence**: Check if session cookies are being cleared properly

### Debug Steps

1. Check the error logs for `[LOGOUT]` or `[ADMIN-LOGOUT]` messages
2. Verify database table structures
3. Test with the `test_logout_termination.php` script
4. Check if the terminate-class-session API is accessible

## Future Enhancements

Potential improvements for the logout termination system:
- Add confirmation dialog before logout
- Implement graceful termination with user notification
- Add termination status reporting
- Implement automatic cleanup for orphaned sessions 