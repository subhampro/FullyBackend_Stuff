<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Track.php accessed at " . date('Y-m-d H:i:s'));

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    exit(json_encode(['status' => 'error', 'message' => 'Please use POST request with JSON data']));
}

$raw_data = file_get_contents('php://input');
error_log("Received raw data: " . $raw_data);

$data = json_decode($raw_data, true);

if (!$data) {
    error_log("Failed to parse JSON data");
    exit(json_encode([
        'status' => 'error', 
        'message' => 'No data received',
        'debug' => [
            'raw_data' => $raw_data,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'request_method' => $_SERVER['REQUEST_METHOD']
        ]
    ]));
}

if (empty($data['user_id']) || empty($data['event'])) {
    exit(json_encode(['status' => 'error', 'message' => 'Missing required fields']));
}

try {
    $db = new PDO('mysql:host=localhost;dbname=wordpres_test', 'wordpres_test', '$$$Pro381998');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    

    $stmt = $db->prepare("INSERT INTO users (user_id, last_seen) 
                         VALUES (:user_id, NOW()) 
                         ON DUPLICATE KEY UPDATE 
                         last_seen = NOW(),
                         total_uses = total_uses + 1");
    
    $stmt->execute(['user_id' => $data['user_id']]);


    $stmt = $db->prepare("INSERT INTO usage_stats 
                         (user_id, event_type, system_info, version, created_at) 
                         VALUES (:user_id, :event, :system, :version, NOW())");
    
    $params = [
        'user_id' => $data['user_id'],
        'event' => $data['event'],
        'system' => isset($data['system']) ? $data['system'] : null,
        'version' => isset($data['version']) ? $data['version'] : '1.0.0'
    ];
    
    $stmt->execute($params);
    error_log("Successfully logged event for user: " . $data['user_id']);

    $stats = [
        'total_users' => $db->query("SELECT COUNT(DISTINCT user_id) FROM users")->fetchColumn(),
        'active_today' => $db->query("SELECT COUNT(DISTINCT user_id) FROM users WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn(),
        'total_conversions' => $db->query("SELECT COUNT(*) FROM usage_stats WHERE event_type = 'conversion'")->fetchColumn()
    ];

    error_log("Successfully processed request for user_id: " . $data['user_id']);
    echo json_encode(['status' => 'ok', 'stats' => $stats]);
    
} catch (PDOException $e) {
    error_log("Database error in track.php: " . $e->getMessage());
    exit(json_encode([
        'status' => 'error', 
        'message' => 'Database error',
        'debug' => [
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ]));
}