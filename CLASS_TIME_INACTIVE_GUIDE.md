# Class Time Inactive Implementation Guide

## Overview

The system now includes a comprehensive termination button that not only terminates the current class session but also sets the class time settings to inactive status. This ensures complete cleanup of class time configurations.

## New Features

### ✅ **Enhanced Termination Buttons**

1. **Header Termination Button** - "Terminate & Set Inactive"
2. **Class Time Settings Button** - "Terminate Session & Set Class Time Inactive"
3. **Automatic Logout Termination** - Sets class time inactive on logout

### ✅ **New API Endpoint**

- **`api/set-class-time-inactive.php`** - Specifically sets class time settings to inactive

### ✅ **Database Enhancement**

- **Status Column** - Automatically adds `status` column to `class_time_settings` table
- **Inactive Status** - Sets status to 'inactive' when terminating

## How It Works

### 1. Termination Process

When a termination button is clicked:

1. **Confirmation Dialog** - User confirms termination
2. **Dual API Calls** - Calls both termination APIs simultaneously:
   - `api/terminate-class-session.php` - Terminates session
   - `api/set-class-time-inactive.php` - Sets class time inactive
3. **Database Updates** - Updates multiple tables
4. **Session Cleanup** - Clears all session variables
5. **UI Updates** - Shows "No Active Session" state

### 2. Database Operations

#### Class Time Settings Table
```sql
UPDATE class_time_settings 
SET status = 'inactive',
    start_time = NULL,
    class_start_time = NULL,
    updated_at = NOW()
WHERE school_id = ?
```

#### Session Variables Cleared
- `class_start_time`
- `class_start_time_formatted`
- `current_instructor_id`
- `current_instructor_name`
- `current_subject_id`
- `current_subject_name`
- `attendance_session_id`
- `attendance_session_start`
- `attendance_session_end`

### 3. API Integration

#### Dual API Call Structure
```javascript
Promise.all([
    fetch('api/terminate-class-session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    }),
    fetch('api/set-class-time-inactive.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
])
.then(responses => Promise.all(responses.map(response => response.json())))
.then(results => {
    const [terminateResult, inactiveResult] = results;
    // Handle both results
});
```

## API Endpoints

### 1. `api/set-class-time-inactive.php`

**Purpose:** Sets class time settings to inactive status

**Method:** POST

**Response:**
```json
{
    "success": true,
    "message": "Class time settings set to inactive successfully",
    "data": {
        "terminated_at": "2024-01-15 14:30:00",
        "previous_class_time": "14:30",
        "previous_instructor": "John Doe",
        "previous_subject": "Web Development",
        "school_id": 1,
        "user_id": 1,
        "email": "user@example.com",
        "affected_rows": 1
    }
}
```

**Features:**
- Automatically adds `status` column if it doesn't exist
- Clears both `start_time` and `class_start_time` columns
- Logs termination activity
- Handles multi-tenant architecture

### 2. Enhanced `api/terminate-class-session.php`

**Purpose:** Terminates attendance sessions and teacher schedules

**Method:** POST

**Features:**
- Terminates attendance sessions
- Sets teacher schedules to inactive
- Clears session variables
- Comprehensive logging

## Button Locations

### 1. Header Termination Button
**Location:** Main page header (top-right)
**Text:** "Terminate & Set Inactive"
**Style:** Large red button with stop icon

### 2. Class Time Settings Button
**Location:** Within class time settings card
**Text:** "Terminate Session & Set Class Time Inactive"
**Style:** Full-width red button with description

## Database Schema

### Class Time Settings Table Enhancement
```sql
ALTER TABLE class_time_settings 
ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER start_time;
```

**Columns:**
- `id` - Primary key
- `school_id` - School identifier
- `start_time` - Class start time (can be NULL)
- `status` - Status ('active' or 'inactive')
- `class_start_time` - Legacy start time (can be NULL)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## Logout Integration

### Enhanced Logout Process
Both `logout.php` and `admin/logout.php` now call both termination APIs:

1. **Terminate Class Session API** - Cleans up sessions
2. **Set Class Time Inactive API** - Sets class time inactive

### Logging
```
[LOGOUT] Successfully called terminate-class-session API
[LOGOUT] Successfully called set-class-time-inactive API
[LOGOUT] Class time activation termination completed for user: user@example.com, school_id: 1
```

## Testing

### Test Files
1. **`test_class_time_inactive.php`** - Comprehensive test interface
2. **`test_termination_simple.php`** - Simple termination test

### Test Scenarios
1. **Button Functionality** - Verify buttons appear and work
2. **API Integration** - Test both APIs individually and together
3. **Database Updates** - Verify status column and data updates
4. **Session Cleanup** - Verify session variables are cleared
5. **UI Updates** - Verify interface updates correctly
6. **Logout Termination** - Verify automatic termination on logout

## Error Handling

### Graceful Degradation
- If one API fails, the other still executes
- If database operations fail, session cleanup continues
- All errors are logged for debugging

### User Feedback
- Loading states during termination
- Success/error messages for both operations
- Confirmation dialogs prevent accidental termination

## Security Features

### Authorization
- Only logged-in users can terminate sessions
- School-specific termination (multi-tenant)
- Session validation before termination

### Data Protection
- Confirmation dialogs prevent accidental termination
- Proper session cleanup
- Database transaction safety

## Multi-Tenant Support

### School Isolation
- Termination only affects current school's data
- Uses `school_id` from session for filtering
- Maintains data isolation between schools

### User Context
- Respects user's school assignment
- Only terminates sessions for user's school
- Logs termination with user context

## Activity Logging

### Termination Logs
```sql
INSERT INTO activity_logs (user_id, school_id, action, details, created_at) 
VALUES (?, ?, 'class_time_inactive', ?, NOW())
```

**Details JSON:**
```json
{
    "previous_class_time": "14:30",
    "previous_instructor": "John Doe",
    "previous_subject": "Web Development",
    "terminated_by": "user@example.com"
}
```

## Troubleshooting

### Common Issues
1. **Status column missing** - API automatically adds it
2. **API calls failing** - Check network connectivity
3. **Database errors** - Check error logs
4. **Session persistence** - Verify session cleanup

### Debug Steps
1. Check browser console for JavaScript errors
2. Verify both APIs are accessible
3. Check database for status column
4. Review error logs for details
5. Test APIs individually

## Files Modified

### New Files
- `api/set-class-time-inactive.php` - New API endpoint
- `test_class_time_inactive.php` - Test interface
- `CLASS_TIME_INACTIVE_GUIDE.md` - This documentation

### Enhanced Files
- `index.php` - Updated termination buttons and function
- `logout.php` - Enhanced with dual API calls
- `admin/logout.php` - Enhanced with dual API calls

## Future Enhancements

### Potential Improvements
1. **Status Dashboard** - Show active/inactive class times
2. **Bulk Operations** - Terminate multiple sessions
3. **Scheduled Termination** - Auto-terminate after time period
4. **Status History** - Track status changes over time
5. **Advanced Filtering** - Filter by status in reports

## Summary

The class time inactive implementation provides:
- ✅ Complete session termination
- ✅ Class time status management
- ✅ Dual API integration
- ✅ Automatic database schema updates
- ✅ Comprehensive logging
- ✅ Multi-tenant support
- ✅ Enhanced user interface
- ✅ Automatic logout integration
- ✅ Testing and documentation

This ensures that class time settings are properly managed with an inactive status, providing better control over class session states and improved data integrity. 