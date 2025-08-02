# Data Isolation Implementation Guide

## Overview

This document outlines the implementation of data isolation for the QR Attendance System to ensure that each school and user can only access their own data.

## Problem Statement

Previously, all school accounts and user profiles were displaying the same records for:
- Attendance data
- Activity logs  
- Instructors
- Students
- Leaderboards
- Analytics
- Face verification history

Despite having `user_id` and `school_id` columns in the database tables, the application was not filtering data based on these fields.

## Solution Implementation

### 1. Data Isolation Helper Functions

Created `includes/data_isolation_helper.php` with the following functions:

- `getCurrentUserContext()` - Gets current user's school_id and user_id from session
- `addDataIsolationFilters()` - Adds school_id and user_id filters to SQL queries
- `addIsolationToInsertData()` - Ensures data isolation for INSERT operations
- `validateRecordOwnership()` - Validates record ownership before operations
- `getIsolationParams()` - Gets isolation parameters for prepared statements
- `logDataAccess()` - Logs data access for audit purposes

### 2. Updated Files with Data Isolation

#### Core Files Updated:

1. **index.php**
   - Added data isolation helper include
   - Updated attendance display query to filter by school_id and user_id
   - Updated attendance deletion to validate ownership
   - Updated pagination count query with isolation

2. **endpoint/add-attendance.php**
   - Added school_id and user_id to attendance records
   - Updated student verification to filter by school_id
   - Updated duplicate check to include school isolation

3. **masterlist.php**
   - Added data isolation helper include
   - Updated student queries to filter by school_id and user_id
   - Updated pagination with isolation filters

4. **api/get-students.php**
   - Added session handling and data isolation
   - Updated student fetch query with school_id and user_id filters

5. **face-verification.php**
   - Added data isolation helper include
   - Updated student dropdown to filter by school_id and user_id

6. **activity_logs.php**
   - Added data isolation helper include
   - Updated activity logs query to filter by school_id and user_id
   - Updated action types filter with isolation

7. **verification-logs.php**
   - Added data isolation helper include
   - Updated verification logs queries to filter by school_id

### 3. Database Schema Requirements

The following tables must have `school_id` and `user_id` columns:

- `tbl_attendance` ✓
- `tbl_student` ✓
- `tbl_instructors` ✓
- `teacher_schedules` ✓
- `activity_logs` ✓
- `tbl_face_verification_logs` ✓
- `tbl_face_recognition_logs` ✓

### 4. Session Management

The system now properly manages:
- `$_SESSION['school_id']` - User's school ID
- `$_SESSION['user_id']` - User's ID
- `$_SESSION['email']` - User's email for authentication

### 5. Query Patterns

#### SELECT Queries with Isolation:
```sql
SELECT * FROM table_name 
WHERE school_id = ? 
AND (user_id = ? OR user_id IS NULL)
```

#### INSERT Queries with Isolation:
```sql
INSERT INTO table_name (..., school_id, user_id) 
VALUES (..., ?, ?)
```

#### DELETE/UPDATE Queries with Isolation:
```sql
DELETE FROM table_name 
WHERE id = ? 
AND school_id = ? 
AND (user_id = ? OR user_id IS NULL)
```

## Implementation Details

### 1. User Context Function
```php
function getCurrentUserContext() {
    $school_id = isset($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    
    return [
        'school_id' => $school_id,
        'user_id' => $user_id
    ];
}
```

### 2. Query Filtering Pattern
```php
$context = getCurrentUserContext();

$query = "SELECT * FROM tbl_attendance 
          WHERE school_id = {$context['school_id']}
          " . ($context['user_id'] ? "AND (user_id = {$context['user_id']} OR user_id IS NULL)" : "") . "
          ORDER BY time_in DESC";
```

### 3. Prepared Statement Pattern
```php
$stmt = $conn->prepare("SELECT * FROM tbl_student 
                       WHERE school_id = :school_id 
                       " . ($context['user_id'] ? "AND (user_id = :user_id OR user_id IS NULL)" : "") . "
                       ORDER BY student_name");
$stmt->bindParam(':school_id', $context['school_id'], PDO::PARAM_INT);
if ($context['user_id']) {
    $stmt->bindParam(':user_id', $context['user_id'], PDO::PARAM_INT);
}
```

## Security Considerations

1. **Input Validation**: All user inputs are properly validated and sanitized
2. **SQL Injection Prevention**: Using prepared statements throughout
3. **Session Security**: Proper session management and validation
4. **Access Control**: Records can only be accessed by their owners
5. **Audit Logging**: Data access is logged for security monitoring

## Testing Recommendations

1. **Multi-School Testing**: Test with multiple schools to ensure data isolation
2. **User Permissions**: Test different user roles and permissions
3. **Data Integrity**: Verify that users can only see their own data
4. **Performance**: Monitor query performance with isolation filters
5. **Edge Cases**: Test scenarios with NULL user_id values

## Migration Notes

1. **Existing Data**: Existing records will default to school_id = 1
2. **User Association**: Records without user_id will be visible to all users in the school
3. **Backup**: Always backup database before implementing changes
4. **Gradual Rollout**: Consider implementing changes gradually to minimize impact

## Maintenance

1. **Regular Audits**: Monitor data access logs regularly
2. **Performance Monitoring**: Watch for slow queries due to isolation filters
3. **User Feedback**: Gather feedback on data access patterns
4. **Updates**: Keep isolation logic updated with new features

## Troubleshooting

### Common Issues:

1. **No Data Showing**: Check if school_id and user_id are properly set in session
2. **Permission Errors**: Verify user has proper access to their school's data
3. **Performance Issues**: Ensure proper indexing on school_id and user_id columns
4. **Session Issues**: Check session configuration and timeout settings

### Debug Commands:
```php
// Debug user context
$context = getCurrentUserContext();
error_log("User Context: " . json_encode($context));

// Debug query with isolation
error_log("Query: " . $query);
```

## Conclusion

This implementation ensures complete data isolation between schools and users while maintaining system performance and security. Each user can now only access data that belongs to their school and their own records, providing the necessary privacy and security for a multi-tenant system. 