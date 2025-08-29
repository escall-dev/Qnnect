<?php
/**
 * School Branding System
 * Handles dynamic branding based on user's school
 */

/**
 * Get school branding information
 */
function getSchoolBranding($conn, $school_id = null) {
    // Use session school_id if not provided
    if (!$school_id) {
        $school_id = $_SESSION['school_id'] ?? null;
    }
    
    if (!$school_id) {
        // Return default branding if no school specified
        return getDefaultBranding();
    }
    
    $sql = "SELECT s.*, u.profile_image as logo_path FROM schools s LEFT JOIN users u ON s.id = u.school_id AND u.role = 'admin' WHERE s.id = ? AND s.status = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        // Fallback to simple query if JOIN fails
        $sql = "SELECT * FROM schools WHERE id = ? AND status = 'active'";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return getDefaultBranding();
        }
    }
    
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $school = mysqli_fetch_assoc($result);
    
    if (!$school) {
        return getDefaultBranding();
    }
    
    return [
        'school_id' => $school['id'],
        'name' => $school['name'],
        'code' => $school['code'],
        'logo_path' => $school['logo_path'] ?: 'image/SPCPC-logo-trans.png',
        'theme_color' => $school['theme_color'] ?: '#098744',
        'secondary_color' => $school['secondary_color'] ?: '#0a5c2e',
        'accent_color' => $school['accent_color'] ?: '#42b883',
        'tagline' => $school['tagline'] ?: 'Track Attendance Seamlessly',
        'description' => $school['description'] ?: 'Modern attendance tracking system',
        'address' => $school['address'],
        'phone' => $school['phone'],
        'email' => $school['email'],
        'website' => $school['website'],
        'principal_name' => $school['principal_name'],
        'school_type' => $school['school_type'],
        'timezone' => $school['timezone'] ?: 'Asia/Manila'
    ];
}

/**
 * Get default branding for fallback
 */
function getDefaultBranding() {
    return [
        'school_id' => null,
        'name' => 'QR Attendance System',
        'code' => 'QAS',
        'logo_path' => 'image/SPCPC-logo-trans.png',
        'theme_color' => '#098744',
        'secondary_color' => '#0a5c2e',
        'accent_color' => '#42b883',
        'tagline' => 'Track Attendance Seamlessly',
        'description' => 'Modern attendance tracking system',
        'address' => null,
        'phone' => null,
        'email' => null,
        'website' => null,
        'principal_name' => null,
        'school_type' => 'college',
        'timezone' => 'Asia/Manila'
    ];
}

/**
 * Generate CSS variables for school theme
 */
function generateSchoolCSS($branding) {
    return "
    <style>
        :root {
            --school-primary: {$branding['theme_color']};
            --school-secondary: {$branding['secondary_color']};
            --school-accent: {$branding['accent_color']};
            --school-name: '{$branding['name']}';
        }
        
        .school-themed {
            background: var(--school-primary);
            color: white;
        }
        
        .school-themed-secondary {
            background: var(--school-secondary);
            color: white;
        }
        
        .school-themed-accent {
            background: var(--school-accent);
            color: white;
        }
        
        .school-text-primary {
            color: var(--school-primary);
        }
        
        .school-border-primary {
            border-color: var(--school-primary);
        }
        
        .btn-school-primary {
            background-color: var(--school-primary);
            border-color: var(--school-primary);
            color: white;
        }
        
        .btn-school-primary:hover {
            background-color: var(--school-secondary);
            border-color: var(--school-secondary);
        }
        
        /* Update existing primary colors */
        .bg-primary, .btn-primary {
            background-color: var(--school-primary) !important;
            border-color: var(--school-primary) !important;
        }
        
        .text-primary {
            color: var(--school-primary) !important;
        }
        
        .border-primary {
            border-color: var(--school-primary) !important;
        }
    </style>";
}

/**
 * Get school-specific logo HTML
 */
function getSchoolLogo($branding, $classes = '', $alt_text = null) {
    $alt = $alt_text ?: $branding['name'] . ' Logo';
    $logo_path = $branding['logo_path'];
    
    // Ensure logo path is properly formatted
    if (!str_starts_with($logo_path, 'http') && !str_starts_with($logo_path, '/')) {
        // Relative path - ensure it starts from root
        if (!str_starts_with($logo_path, 'image/')) {
            $logo_path = 'image/' . $logo_path;
        }
    }
    
    return "<img src='{$logo_path}' alt='{$alt}' class='{$classes}'>";
}

/**
 * Get school-specific header HTML
 */
function getSchoolHeader($branding, $include_tagline = true) {
    $logo = getSchoolLogo($branding, 'school-logo', $branding['name']);
    $tagline = $include_tagline ? "<p class='school-tagline'>{$branding['tagline']}</p>" : '';
    
    return "
    <div class='school-header'>
        {$logo}
        <h1 class='school-name'>{$branding['name']}</h1>
        {$tagline}
    </div>";
}

/**
 * Apply school branding to page
 */
function applySchoolBranding($conn, $school_id = null) {
    $branding = getSchoolBranding($conn, $school_id);
    
    // Store branding in session for quick access
    $_SESSION['school_branding'] = $branding;
    
    // Output CSS
    echo generateSchoolCSS($branding);
    
    return $branding;
}

/**
 * Get school branding from session (faster than DB query)
 */
function getSessionSchoolBranding() {
    return $_SESSION['school_branding'] ?? getDefaultBranding();
}

/**
 * Update school branding in database
 */
function updateSchoolBranding($conn, $school_id, $branding_data) {
    // Validate required fields
    $required_fields = ['name', 'code', 'theme_color'];
    foreach ($required_fields as $field) {
        if (empty($branding_data[$field])) {
            return ['success' => false, 'message' => "Field '{$field}' is required"];
        }
    }
    
    // Validate color format
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $branding_data['theme_color'])) {
        return ['success' => false, 'message' => 'Invalid theme color format'];
    }
    
    // Build update query
    $fields = [
        'name', 'code', 'logo_path', 'theme_color', 'secondary_color', 
        'accent_color', 'tagline', 'description', 'address', 'phone', 
        'email', 'website', 'principal_name', 'school_type', 'timezone'
    ];
    
    $set_clauses = [];
    $values = [];
    $types = '';
    
    foreach ($fields as $field) {
        if (isset($branding_data[$field])) {
            $set_clauses[] = "{$field} = ?";
            $values[] = $branding_data[$field];
            $types .= 's';
        }
    }
    
    if (empty($set_clauses)) {
        return ['success' => false, 'message' => 'No valid fields to update'];
    }
    
    $values[] = $school_id;
    $types .= 'i';
    
    $sql = "UPDATE schools SET " . implode(', ', $set_clauses) . ", updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    
    if (mysqli_stmt_execute($stmt)) {
        // Clear session branding to force refresh
        unset($_SESSION['school_branding']);
        
        // Log the activity
        if (function_exists('logActivity')) {
            logActivity($conn, 'SCHOOL_BRANDING_UPDATED', "School ID: {$school_id}");
        }
        
        return ['success' => true, 'message' => 'School branding updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to update school branding'];
}

/**
 * Get all schools with branding info (for super admin)
 */
function getAllSchoolsBranding($conn) {
    $sql = "SELECT * FROM schools WHERE status = 'active' ORDER BY name";
    $result = mysqli_query($conn, $sql);
    
    $schools = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $schools[] = [
            'school_id' => $row['id'],
            'name' => $row['name'],
            'code' => $row['code'],
            'logo_path' => $row['logo_path'] ?: 'image/SPCPC-logo-trans.png',
            'theme_color' => $row['theme_color'] ?: '#098744',
            'secondary_color' => $row['secondary_color'] ?: '#0a5c2e',
            'accent_color' => $row['accent_color'] ?: '#42b883',
            'tagline' => $row['tagline'] ?: 'Track Attendance Seamlessly',
            'description' => $row['description'] ?: 'Modern attendance tracking system',
            'address' => $row['address'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'website' => $row['website'],
            'principal_name' => $row['principal_name'],
            'school_type' => $row['school_type'],
            'timezone' => $row['timezone'] ?: 'Asia/Manila',
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    return $schools;
}

/**
 * Validate school access for current user
 */
function validateSchoolAccess($conn, $school_id) {
    // Super admins can access all schools
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
        return true;
    }
    
    // Regular users can only access their assigned school
    $user_school_id = $_SESSION['school_id'] ?? null;
    return $user_school_id == $school_id;
}

/**
 * Get school-specific dashboard data
 */
function getSchoolDashboardData($conn, $school_id) {
    if (!validateSchoolAccess($conn, $school_id)) {
        return ['error' => 'Access denied to this school'];
    }
    
    $data = [];
    
    // Get student count
    $sql = "SELECT COUNT(*) as count FROM tbl_student WHERE school_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data['student_count'] = mysqli_fetch_assoc($result)['count'];
    
    // Get instructor count
    $sql = "SELECT COUNT(*) as count FROM tbl_instructors WHERE school_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data['instructor_count'] = mysqli_fetch_assoc($result)['count'];
    
    // Get subject count
    $sql = "SELECT COUNT(*) as count FROM tbl_subjects WHERE school_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data['subject_count'] = mysqli_fetch_assoc($result)['count'];
    
    // Get today's attendance count
    $sql = "SELECT COUNT(*) as count FROM tbl_attendance WHERE school_id = ? AND DATE(date_created) = CURDATE()";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data['today_attendance'] = mysqli_fetch_assoc($result)['count'];
    
    return $data;
}
?>