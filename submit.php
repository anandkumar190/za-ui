<?php
header('Content-Type: application/json');
require_once 'config.php';

// Accept both raw JSON and standard URL-encoded form POST inputs
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}


$name = isset($input['name']) ? trim($input['name']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$address = isset($input['address']) ? trim($input['address']) : '';
$pin = isset($input['pin']) ? trim($input['pin']) : '';
$platform = isset($input['platform']) ? trim($input['platform']) : 'Direct / Organic';

// Get client IP address
$ipAddress = '';
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipAddress = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
} else {
    $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
}
$ipAddress = trim($ipAddress);

// Get User Agent and detect device type
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$secChUa = isset($_SERVER['HTTP_SEC_CH_UA']) ? $_SERVER['HTTP_SEC_CH_UA'] : '';
if ($secChUa) {
    $userAgent .= ' [Client Hints: ' . $secChUa . ']';
}

$device = 'Desktop/Laptop';
if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $userAgent)) {
    $device = 'Tablet';
} elseif (preg_match('/(up\.browser|up\.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile|iphone|ipad|ipod|blackberry|nokia|htc|motorola|webos)/i', $userAgent)) {
    $device = 'Mobile';
}

// 1. Basic empty check
if (empty($name) || empty($phone) || empty($address) || empty($pin)) {
    echo json_encode(['success' => false, 'message' => 'कृपया सभी आवश्यक फ़ील्ड भरें। (Please fill out all required fields.)']);
    exit;
}

// 2. Mobile number validation (Indian mobile: starts with 6-9, 10 digits)
if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'कृपया एक वैध 10 अंकों का मोबाइल नंबर दर्ज करें। (Please enter a valid 10-digit mobile number.)']);
    exit;
}

// 3. PIN code validation (exactly 6 digits)
if (!preg_match('/^\d{6}$/', $pin)) {
    echo json_encode(['success' => false, 'message' => 'कृपया एक वैध 6 अंकों का PIN कोड दर्ज करें। (Please enter a valid 6-digit PIN code.)']);
    exit;
}

try {
    // Insert into database with tracking fields
    $stmt = $pdo->prepare("INSERT INTO inquiries (name, phone, address, pin, ip_address, platform, user_agent, device, status) VALUES (:name, :phone, :address, :pin, :ip_address, :platform, :user_agent, :device, 'Pending')");
    $result = $stmt->execute([
        ':name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        ':phone' => htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),
        ':address' => htmlspecialchars($address, ENT_QUOTES, 'UTF-8'),
        ':pin' => htmlspecialchars($pin, ENT_QUOTES, 'UTF-8'),
        ':ip_address' => htmlspecialchars($ipAddress, ENT_QUOTES, 'UTF-8'),
        ':platform' => htmlspecialchars($platform, ENT_QUOTES, 'UTF-8'),
        ':user_agent' => htmlspecialchars($userAgent, ENT_QUOTES, 'UTF-8'),
        ':device' => htmlspecialchars($device, ENT_QUOTES, 'UTF-8')
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => '🎉 बधाई हो! आपका ऑर्डर सफलतापूर्वक दर्ज हो गया है। हमारी टीम जल्द ही आपसे संपर्क करेगी।']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ऑर्डर दर्ज करने में विफलता। कृपया बाद में पुनः प्रयास करें।']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'डेटाबेस त्रुटि: ' . $e->getMessage()]);
}
?>
