    <?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect_pdo.php';

header('Content-Type: application/json');
ob_clean();
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_school_id = $_SESSION['school_id'] ?? 1;

try {
    $pdo = $conn_qr_pdo;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'add':
                    $holiday_date = $data['holiday_date'] ?? '';
                    $holiday_name = $data['holiday_name'] ?? '';
                    
                    if (empty($holiday_date) || empty($holiday_name)) {
                        echo json_encode(['success' => false, 'message' => 'Date and name are required']);
                        exit;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO holidays (school_id, holiday_date, holiday_name) VALUES (?, ?, ?)");
                    $stmt->execute([$user_school_id, $holiday_date, $holiday_name]);
                    
                    echo json_encode(['success' => true, 'message' => 'Holiday added successfully']);
                    break;
                    
                case 'delete':
                    $holiday_id = $data['holiday_id'] ?? '';
                    
                    if (empty($holiday_id)) {
                        echo json_encode(['success' => false, 'message' => 'Holiday ID is required']);
                        exit;
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM holidays WHERE id = ? AND school_id = ?");
                    $stmt->execute([$holiday_id, $user_school_id]);
                    
                    echo json_encode(['success' => true, 'message' => 'Holiday deleted successfully']);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Action is required']);
        }
    } else {
        // GET request - fetch holidays for a month
        $month = $_GET['month'] ?? date('Y-m');
        $year = date('Y', strtotime($month));
        $month_num = date('n', strtotime($month));
        
        $stmt = $pdo->prepare("
            SELECT id, holiday_date, holiday_name 
            FROM holidays 
            WHERE school_id = ? AND YEAR(holiday_date) = ? AND MONTH(holiday_date) = ?
            ORDER BY holiday_date
        ");
        $stmt->execute([$user_school_id, $year, $month_num]);
        $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'holidays' => $holidays]);
    }
    
} catch (Exception $e) {
    error_log("Holiday management error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 