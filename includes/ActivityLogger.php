<?php
class ActivityLogger {
    private $conn;
    private $user_id;
    private $ip_address;
    private $user_agent;

    public function __construct($conn, $user_id = null) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    public function log($action_type, $action_description, $affected_table = null, $affected_id = null, $additional_data = null) {
        $sql = "INSERT INTO activity_logs (
            user_id, action_type, action_description, affected_table, 
            affected_id, ip_address, user_agent, created_at, additional_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $stmt = $this->conn->prepare($sql);
        $additional_data_json = $additional_data ? json_encode($additional_data) : null;
        
        $stmt->bind_param(
            "isssisss",
            $this->user_id,
            $action_type,
            $action_description,
            $affected_table,
            $affected_id,
            $this->ip_address,
            $this->user_agent,
            $additional_data_json
        );

        return $stmt->execute();
    }

    public function cacheOfflineData($table_name, $action_type, $data) {
        $sql = "INSERT INTO offline_data (
            table_name, action_type, data, created_at
        ) VALUES (?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $data_json = json_encode($data);
        
        $stmt->bind_param(
            "sss",
            $table_name,
            $action_type,
            $data_json
        );

        return $stmt->execute();
    }

    public function syncOfflineData() {
        // Get all pending offline data
        $sql = "SELECT * FROM offline_data WHERE status = 'pending' ORDER BY created_at ASC";
        $result = $this->conn->query($sql);

        while ($row = $result->fetch_assoc()) {
            $data = json_decode($row['data'], true);
            $success = false;

            try {
                switch ($row['action_type']) {
                    case 'insert':
                        $success = $this->processInsert($row['table_name'], $data);
                        break;
                    case 'update':
                        $success = $this->processUpdate($row['table_name'], $data);
                        break;
                    case 'delete':
                        $success = $this->processDelete($row['table_name'], $data);
                        break;
                }

                if ($success) {
                    // Update offline data status to synced
                    $update_sql = "UPDATE offline_data SET 
                        status = 'synced', 
                        synced_at = NOW() 
                        WHERE id = ?";
                    $stmt = $this->conn->prepare($update_sql);
                    $stmt->bind_param("i", $row['id']);
                    $stmt->execute();

                    // Log the successful sync
                    $this->log(
                        'offline_sync',
                        "Successfully synced offline data for {$row['table_name']}",
                        $row['table_name'],
                        $row['id']
                    );
                }
            } catch (Exception $e) {
                // Update sync attempts and error message
                $update_sql = "UPDATE offline_data SET 
                    sync_attempts = sync_attempts + 1,
                    error_message = ?,
                    status = CASE 
                        WHEN sync_attempts >= 3 THEN 'failed'
                        ELSE 'pending'
                    END
                    WHERE id = ?";
                $stmt = $this->conn->prepare($update_sql);
                $error_msg = $e->getMessage();
                $stmt->bind_param("si", $error_msg, $row['id']);
                $stmt->execute();
            }
        }
    }

    private function processInsert($table_name, $data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table_name ($columns) VALUES ($values)";
        
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($data));
        $stmt->bind_param($types, ...array_values($data));
        
        return $stmt->execute();
    }

    private function processUpdate($table_name, $data) {
        $id = $data['id'];
        unset($data['id']);
        
        $set_clause = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE $table_name SET $set_clause WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($data)) . 'i';
        $values = array_values($data);
        $values[] = $id;
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    private function processDelete($table_name, $data) {
        $sql = "DELETE FROM $table_name WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $data['id']);
        return $stmt->execute();
    }

    public function exportActivityLogs($format = 'csv', $start_date = null, $end_date = null, $action_type = null, $user_id = null) {
        // Build query conditions
        $where_conditions = [];
        $params = [];
        $types = "";

        if ($start_date && $end_date) {
            $where_conditions[] = "al.created_at BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $types .= "ss";
        }

        if ($action_type) {
            $where_conditions[] = "al.action_type = ?";
            $params[] = $action_type;
            $types .= "s";
        }

        if ($user_id) {
            $where_conditions[] = "al.user_id = ?";
            $params[] = $user_id;
            $types .= "i";
        }

        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

        // Simplified query without user JOIN to avoid database cross-reference issues
        $sql = "SELECT 
            al.*
            FROM activity_logs al
            $where_clause
            ORDER BY al.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Try to get user info for this log entry
            $userData = $this->getUserInfo($row['user_id']);
            $row['user_email'] = $userData['email'] ?? 'No email available';
            $row['user_name'] = $userData['name'] ?? 'User '.$row['user_id'];
            
            $data[] = $row;
        }

        // If no data, return empty file with headers
        if (empty($data)) {
            $data[] = [
                'id' => '',
                'user_id' => '',
                'action_type' => '',
                'action_description' => '',
                'created_at' => '',
                'ip_address' => '',
                'user_email' => '',
                'user_name' => ''
            ];
        }

        switch ($format) {
            case 'csv':
                return $this->exportToCSV($data);
            case 'excel':
                return $this->exportToExcel($data);
            case 'pdf':
                return $this->exportToPDF($data);
            default:
                throw new Exception("Unsupported export format");
        }
    }
    
    private function getUserInfo($user_id) {
        if (empty($user_id)) {
            return ['email' => 'System', 'name' => 'System'];
        }
        
        // Try to connect to login_register database
        $conn_login = null;
        try {
            $conn_login = mysqli_connect("localhost", "root", "", "login_register");
            if ($conn_login) {
                // Use only columns that exist in the table
                $sql = "SELECT id, username, email, full_name FROM users WHERE id = ?";
                $stmt = $conn_login->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $userData = $result->fetch_assoc();
                    return [
                        'email' => $userData['email'],
                        'name' => $userData['username'] ?: $userData['full_name'] ?: $userData['email'] // Use username, then full_name, then email as fallback
                    ];
                }
                // Safely close the connection
                if (isset($conn_login) && $conn_login instanceof mysqli) {
                    try {
                        if ($conn_login->ping()) {
                            $conn_login->close();
                        }
                    } catch (Throwable $e) {
                        // Connection is already closed or invalid, do nothing
                    }
                }
            }
        } catch (Exception $e) {
            // Just return default values on failure
        }
        
        return ['email' => 'User '.$user_id, 'name' => 'User '.$user_id];
    }

    private function exportToCSV($data) {
        $filename = "activity_logs_" . date('Y-m-d_H-i-s') . ".csv";
        $output = fopen('php://temp', 'r+');

        // Add headers
        fputcsv($output, array_keys($data[0]));

        // Skip adding data if it's our empty placeholder
        if (count($data) == 1 && empty($data[0]['id'])) {
            // Don't add the empty row
        } else {
            // Add data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return [
            'content' => $csv,
            'filename' => $filename,
            'type' => 'text/csv'
        ];
    }

    private function exportToExcel($data) {
        // Simple Excel export without PhpSpreadsheet dependency
        $filename = "activity_logs_" . date('Y-m-d_H-i-s') . ".csv";
        $output = fopen('php://temp', 'r+');

        // Add headers
        fputcsv($output, array_keys($data[0]));

        // Skip adding data if it's our empty placeholder
        if (count($data) == 1 && empty($data[0]['id'])) {
            // Don't add the empty row
        } else {
            // Add data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return [
            'content' => $csv,
            'filename' => $filename,
            'type' => 'text/csv'
        ];
    }

    private function exportToPDF($data) {
        // For the simplified version, we'll just return a CSV since TCPDF might not be available
        return $this->exportToCSV($data);
    }
}
?> 