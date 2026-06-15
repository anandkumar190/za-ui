<?php
header('Content-Type: application/json');
require_once 'config.php';

// Accept both raw JSON and standard URL-encoded form POST inputs
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$name = isset($input['name']) ? trim($input['name']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$address = isset($input['address']) ? trim($input['address']) : '';
$pin = isset($input['pin']) ? trim($input['pin']) : '';

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
    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO inquiries (name, phone, address, pin, status) VALUES (:name, :phone, :address, :pin, 'Pending')");
    $result = $stmt->execute([
        ':name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        ':phone' => htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),
        ':address' => htmlspecialchars($address, ENT_QUOTES, 'UTF-8'),
        ':pin' => htmlspecialchars($pin, ENT_QUOTES, 'UTF-8')
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
