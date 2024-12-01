<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once('db_connect.php');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['machine_id'])) {
    exit(json_encode(['status' => 'error', 'message' => 'Invalid request data']));
}

$machine_id = $data['machine_id'];

try {
    $stmt = $db->prepare("SELECT * FROM licenses WHERE machine_id = ? AND status = 'active'");
    $stmt->execute([$machine_id]);
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($license) {
        $expires_at = strtotime($license['expires_at']);
        $now = time();
        
        if ($expires_at > $now) {
            $remaining = $expires_at - $now;
            echo json_encode([
                'status' => 'valid',
                'type' => 'full',
                'remaining' => [
                    'days' => floor($remaining / 86400),
                    'hours' => floor(($remaining % 86400) / 3600),
                    'minutes' => floor(($remaining % 3600) / 60)
                ]
            ]);
            exit;
        }
        
        echo json_encode([
            'status' => 'expired',
            'message' => 'License has expired'
        ]);
        exit;
    }
    
    $stmt = $db->prepare("SELECT * FROM usage_stats WHERE user_id = ? ORDER BY created_at ASC LIMIT 1");
    $stmt->execute([$machine_id]);
    $first_use = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($first_use) {
        $trial_end = strtotime($first_use['created_at'] . ' +7 days');
        $now = time();
        
        if ($now <= $trial_end) {
            $remaining = $trial_end - $now;
            echo json_encode([
                'status' => 'valid',
                'type' => 'trial',
                'remaining' => [
                    'days' => floor($remaining / 86400),
                    'hours' => floor(($remaining % 86400) / 3600),
                    'minutes' => floor(($remaining % 3600) / 60)
                ]
            ]);
            exit;
        }
        
        echo json_encode([
            'status' => 'expired',
            'message' => 'Trial period has expired. Please activate a license to continue.'
        ]);
        exit;
    }
    
    echo json_encode([
        'status' => 'valid',
        'type' => 'trial',
        'remaining' => [
            'days' => 7,
            'hours' => 0,
            'minutes' => 0
        ],
        'message' => 'Trial period started'
    ]);
    
} catch (Exception $e) {
    error_log("License verification error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Verification failed. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}