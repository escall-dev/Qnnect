<?php
/**
 * Data Isolation Helper Functions
 * 
 * This file contains helper functions to ensure proper data isolation
 * between different schools and users in the QR attendance system.
 */

/**
 * Get the current user's school_id and user_id from session
 * 
 * @return array Array containing school_id and user_id
 */
function getCurrentUserContext() {
    $school_id = isset($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    
    return [
        'school_id' => $school_id,
        'user_id' => $user_id
    ];
}

/**
 * Add school_id and user_id filters to SQL queries
 * 
 * @param string $sql The original SQL query
 * @param array $context User context from getCurrentUserContext()
 * @param string $table_alias The table alias to use for filtering
 * @return string Modified SQL query with isolation filters
 */
function addDataIsolationFilters($sql, $context, $table_alias = '') {
    $school_id = $context['school_id'];
    $user_id = $context['user_id'];
    
    $alias = !empty($table_alias) ? $table_alias . '.' : '';
    
    // Build WHERE clause for isolation
    $where_conditions = [];
    
    // Always filter by school_id if the table has it
    if (strpos($sql, 'school_id') !== false) {
        $where_conditions[] = "{$alias}school_id = {$school_id}";
    }
    
    // Filter by user_id if available and the table has it
    if ($user_id && strpos($sql, 'user_id') !== false) {
        $where_conditions[] = "({$alias}user_id = {$user_id} OR {$alias}user_id IS NULL)";
    }
    
    // Add the WHERE clause to the query
    if (!empty($where_conditions)) {
        $where_clause = implode(' AND ', $where_conditions);
        
        if (stripos($sql, 'WHERE') !== false) {
            // Query already has WHERE clause, add AND
            $sql = str_replace('WHERE', "WHERE {$where_clause} AND", $sql);
        } else {
            // Query doesn't have WHERE clause, add it
            $sql .= " WHERE {$where_clause}";
        }
    }
    
    return $sql;
}

/**
 * Ensure data isolation for INSERT operations
 * 
 * @param array $data The data to be inserted
 * @param array $context User context from getCurrentUserContext()
 * @return array Modified data with school_id and user_id
 */
function addIsolationToInsertData($data, $context) {
    $data['school_id'] = $context['school_id'];
    
    // Only add user_id if it's available and the table supports it
    if ($context['user_id'] && array_key_exists('user_id', $data)) {
        $data['user_id'] = $context['user_id'];
    }
    
    return $data;
}

/**
 * Validate that a record belongs to the current user's context
 * 
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param int $record_id Record ID to validate
 * @param array $context User context from getCurrentUserContext()
 * @return bool True if record belongs to user, false otherwise
 */
function validateRecordOwnership($conn, $table, $record_id, $context) {
    $school_id = $context['school_id'];
    $user_id = $context['user_id'];
    
    $sql = "SELECT COUNT(*) as count FROM {$table} WHERE id = ? AND school_id = ?";
    $params = [$record_id, $school_id];
    
    // Add user_id check if available
    if ($user_id) {
        $sql .= " AND (user_id = ? OR user_id IS NULL)";
        $params[] = $user_id;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

/**
 * Get isolation-aware query parameters for prepared statements
 * 
 * @param array $context User context from getCurrentUserContext()
 * @return array Array with school_id and user_id for binding
 */
function getIsolationParams($context) {
    return [
        'school_id' => $context['school_id'],
        'user_id' => $context['user_id']
    ];
}

/**
 * Log data access for audit purposes
 * 
 * @param string $action The action performed (SELECT, INSERT, UPDATE, DELETE)
 * @param string $table The table accessed
 * @param array $context User context
 * @param string $details Additional details about the operation
 */
function logDataAccess($action, $table, $context, $details = '') {
    // This function can be used to log data access for audit purposes
    // Implementation depends on your logging requirements
    error_log("Data Access: {$action} on {$table} by user_id:{$context['user_id']}, school_id:{$context['school_id']} - {$details}");
}
?> 