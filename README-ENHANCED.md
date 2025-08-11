- Performance note: Logout optimized
  - Removed blocking HTTP calls in `admin/logout.php`
  - Optimized `attendance_sessions` termination queries to avoid `DATE()` on `start_time`
  - Recommended indexes for `attendance_sessions`: `INDEX(school_id, start_time)`, `INDEX(start_time)`, `INDEX(end_time)`
# 📘 Qnnect - Enhanced Multi-Tenant QR Attendance System

## 🎯 Recent Improvements (Multi-Tenant & Enhanced Features)

### ✅ Implemented Features

#### 1. **Multi-Tenant Dataset Isolation** 
- **School-Specific Data**: All records (students, attendance, schedules) are isolated by `school_id`
- **User-Specific Access**: Additional filtering by `user_id` ensures user-level data segregation
- **Database Schema**: Enhanced tables with compound indexes for optimal performance
- **Security**: Strict validation ensures no cross-contamination between schools/users

#### 2. **Flexible Attendance Mode Toggle**
- **📋 General Attendance Mode**: Manual instructor/subject selection
- **📘 Room & Subject-based Mode**: Schedule-integrated attendance with automatic time detection
- **Seamless Switching**: Toggle between modes with persistent user preference
- **Context-Aware UI**: Interface adapts based on selected mode

#### 3. **Enhanced Schedule Integration**
- **Dynamic Schedule Loading**: Real-time AJAX loading of teacher schedules
- **Auto-Time Setting**: Automatic class time detection from schedule data
- **Schedule Validation**: Comprehensive validation of instructor/section/subject combinations
- **Smart Defaults**: Automatic creation of schedules when none exist

#### 4. **Strict Record Association**
- **Compound Indexing**: `(school_id, user_id, student_id, datetime)` prevents duplicates
- **Session Validation**: Enhanced session management prevents unauthorized access
- **Audit Logging**: Comprehensive audit trail for all multi-tenant operations

#### 5. **Real-Time Attendance Updates**
- **Live Table Updates**: Attendance table refreshes automatically after QR scans
- **WebSocket Alternative**: Polling-based real-time updates (5-second intervals)
- **Visual Feedback**: Smooth animations for new attendance records
- **Notification System**: Toast notifications for new attendance entries

#### 6. **Enhanced QR Session Management**
- **Token-Based Validation**: Improved session handling for QR scanning
- **Session Persistence**: Prevents "Session Expired" errors during scanning
- **Enhanced Error Handling**: Detailed error context and recovery mechanisms
- **Multi-Device Support**: Better handling of concurrent sessions

---

## 🚀 Installation & Setup

### 1. **Database Setup**
```bash
# Run the multi-tenant database setup
http://localhost/Qnnect/setup-multi-tenant-db.php
```

### 2. **File Structure**
```
Qnnect/
├── index.php (Enhanced with attendance modes)
├── endpoint/add-attendance.php (Multi-tenant support)
├── api/
│   ├── get-schedule.php (Schedule loading)
│   ├── get-latest-attendance.php (Real-time updates)
│   ├── validate-session.php (Session management)
│   └── set-schedule-session.php (Schedule session storage)
├── setup-multi-tenant-db.php (Database setup)
└── includes/
    ├── session_config.php (Enhanced session handling)
    └── schedule_helper.php (Schedule utilities)
```

---

## 🎮 Usage Guide

### **Attendance Mode Selection**
1. **Access the main page** (`index.php`)
2. **Choose attendance mode** from the dropdown:
   - **📋 General Attendance**: For manual tracking
   - **📘 Room & Subject-based**: For schedule integration

### **General Mode Workflow**
1. Select attendance mode: "General Attendance"
2. Manually select instructor and subject
3. Set class start time
4. Scan QR codes for attendance

### **Room & Subject Mode Workflow**
1. Select attendance mode: "Room and Subject-based Attendance"
2. Choose instructor, section, and subject from dropdowns
3. Click "Load Schedule" to auto-configure class time
4. Scan QR codes with automatic schedule integration

### **Real-Time Features**
- **Live Updates**: Attendance table updates automatically every 5 seconds
- **Visual Notifications**: New attendance records appear with animations
- **Session Monitoring**: Automatic session validation prevents timeouts

---

## 🔧 Technical Architecture

### **Multi-Tenant Data Model**
```sql
-- Enhanced table structure
tbl_student (
    tbl_student_id, student_name, generated_code,
    school_id, user_id,  -- Multi-tenant keys
    INDEX(school_id, user_id)
)

tbl_attendance (
    tbl_attendance_id, tbl_student_id, time_in, status,
    instructor_id, subject_id,
    school_id, user_id,  -- Multi-tenant keys
    INDEX(school_id, user_id, tbl_student_id, DATE(time_in))
)

class_schedules (
    id, instructor_name, course_section, subject,
    start_time, end_time, day_of_week,
    school_id, user_id,  -- Multi-tenant keys
    UNIQUE(instructor_name, course_section, subject, day_of_week, school_id, user_id)
)
```

### **Session Management**
```php
// Enhanced session validation
$_SESSION['school_id']  // School isolation
$_SESSION['user_id']    // User isolation
$_SESSION['attendance_mode']  // Mode persistence
$_SESSION['current_schedule_id']  // Schedule context
```

### **API Endpoints**
- **`/api/get-schedule.php`**: Dynamic schedule loading with school/user filtering
- **`/api/get-latest-attendance.php`**: Real-time attendance updates
- **`/api/validate-session.php`**: Session validation for QR scanning
- **`/api/set-schedule-session.php`**: Schedule session management

---

## 🛡️ Security Features

### **Data Isolation**
- **School-Level Isolation**: All queries filtered by `school_id`
- **User-Level Filtering**: Additional `user_id` validation
- **Compound Indexes**: Prevent cross-tenant data access
- **Session Validation**: Continuous session monitoring

### **QR Scanning Security**
- **Token-Based Validation**: Enhanced session management
- **IP Tracking**: Audit logs include IP addresses
- **Rate Limiting**: Prevents rapid-fire scanning abuse
- **Error Context**: Detailed logging without exposing sensitive data

### **Audit Trail**
```sql
audit_logs (
    log_id, user_id, school_id, action, table_name,
    record_id, old_values, new_values,
    ip_address, user_agent, created_at
)
```

---

## 🔍 Troubleshooting

### **Common Issues**

#### **"Session Expired" Error**
- **Solution**: Enhanced session management prevents this
- **Fallback**: Automatic session refresh every 2 minutes
- **Recovery**: Graceful redirect to login with context preservation

#### **Schedule Not Loading**
- **Check**: Ensure all three fields (instructor/section/subject) are selected
- **Auto-Creation**: System creates sample schedules if none exist
- **Validation**: Real-time validation of schedule combinations

#### **Real-Time Updates Not Working**
- **Check**: Browser console for JavaScript errors
- **Fallback**: Manual page refresh still works
- **Network**: Ensure AJAX requests aren't blocked

#### **Multi-Tenant Data Mixing**
- **Database**: Run `setup-multi-tenant-db.php` to fix schema
- **Session**: Clear browser cache and re-login
- **Validation**: Check session variables for correct school_id/user_id

---

## 📊 Performance Optimizations

### **Database Indexes**
- **Compound indexes** on `(school_id, user_id)` for all multi-tenant tables
- **Specialized indexes** for attendance lookups and QR validation
- **Unique constraints** prevent duplicate attendance records

### **Frontend Optimizations**
- **AJAX calls** instead of full page reloads
- **Local storage** for user preferences
- **Debounced requests** for schedule loading
- **Efficient DOM updates** for real-time features

### **Session Management**
- **Minimal session data** for faster validation
- **Periodic cleanup** of expired sessions
- **Optimized session storage** with secure cookies

---

## 🎯 Future Enhancements

- **WebSocket integration** for true real-time updates
- **Mobile app** with enhanced QR scanning
- **Advanced reporting** with multi-tenant analytics
- **Bulk operations** for attendance management
- **Integration APIs** for external systems

---

## 📞 Support

For technical support or questions:
- **Documentation**: Check this README for common solutions
- **Database Issues**: Run the setup script at `/setup-multi-tenant-db.php`
- **Session Problems**: Clear browser cache and check session configuration
- **Multi-Tenant Issues**: Verify school_id and user_id in session data

---

**Last Updated**: January 31, 2025  
**Version**: 2.0 (Multi-Tenant Enhanced)
