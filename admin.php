<?php
// === SIMPLE AUTHENTICATION ===
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123'); // You can change this to your preferred password

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    ($_SERVER['PHP_AUTH_USER'] !== ADMIN_USER) || ($_SERVER['PHP_AUTH_PW'] !== ADMIN_PASS)) {
    header('WWW-Authenticate: Basic realm="OJAS+ Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h2>Unauthorized access.</h2>';
    exit;
}

require_once 'config.php';

// === ACTIONS ===

// 1. Delete Lead
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        // Log or show error
    }
    header('Location: admin.php');
    exit;
}

// 2. Change Status (Pending / Confirmed / Cancelled)
if (isset($_GET['action']) && $_GET['action'] === 'status' && isset($_GET['id']) && isset($_GET['value'])) {
    $id = intval($_GET['id']);
    $value = trim($_GET['value']);
    if (in_array($value, ['Pending', 'Confirmed', 'Cancelled'])) {
        try {
            $stmt = $pdo->prepare("UPDATE inquiries SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $value, ':id' => $id]);
        } catch (PDOException $e) {
            // Log or show error
        }
    }
    header('Location: admin.php');
    exit;
}

// 3. Export CSV
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
    
    if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $startDate = '';
    }
    if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $endDate = '';
    }
    
    $whereClauses = [];
    $params = [];
    
    if ($startDate) {
        $whereClauses[] = "created_at >= :start_date";
        $params[':start_date'] = $startDate . ' 00:00:00';
    }
    if ($endDate) {
        $whereClauses[] = "created_at <= :end_date";
        $params[':end_date'] = $endDate . ' 23:59:59';
    }
    
    $whereSQL = "";
    if (!empty($whereClauses)) {
        $whereSQL = " WHERE " . implode(" AND ", $whereClauses);
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ojas_leads_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for proper excel encoding of Hindi names
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Order ID', 'Date & Time', 'Name', 'Phone', 'Address', 'PIN Code', 'Platform', 'Device', 'IP Address', 'User Agent', 'Status']);
    
    $stmt = $pdo->prepare("SELECT * FROM inquiries" . $whereSQL . " ORDER BY id DESC");
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            'ORD-' . $row['id'],
            $row['created_at'],
            $row['name'],
            $row['phone'],
            $row['address'],
            $row['pin'],
            isset($row['platform']) ? $row['platform'] : 'N/A',
            isset($row['device']) ? $row['device'] : 'N/A',
            isset($row['ip_address']) ? $row['ip_address'] : 'N/A',
            isset($row['user_agent']) ? $row['user_agent'] : 'N/A',
            $row['status']
        ]);
    }
    fclose($output);
    exit;
}

// 4. Replace Image / GIF
if (isset($_POST['action']) && $_POST['action'] === 'replace_image') {
    if (isset($_POST['target_file']) && isset($_FILES['replacement_file'])) {
        $target = trim($_POST['target_file']);
        
        // Security checks: Must be inside 'images/' and no directory traversal
        if (strpos($target, 'images/') !== 0 || strpos($target, '..') !== false) {
            header('Location: admin.php?status=error&msg=' . urlencode('सुरक्षा त्रुटि: अवैध फ़ाइल पथ। (Security Error: Invalid file path.)') . '#media-manager');
            exit;
        }
        
        if (!file_exists($target)) {
            header('Location: admin.php?status=error&msg=' . urlencode('त्रुटि: लक्षित फ़ाइल मौजूद नहीं है। (Error: Target file does not exist.)') . '#media-manager');
            exit;
        }

        $file = $_FILES['replacement_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            header('Location: admin.php?status=error&msg=' . urlencode('अपलोड त्रुटि कोड: ' . $file['error']) . '#media-manager');
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            header('Location: admin.php?status=error&msg=' . urlencode('अवैध फ़ाइल प्रकार। केवल JPG, PNG, GIF, WEBP की अनुमति है।') . '#media-manager');
            exit;
        }

        $mime = mime_content_type($file['tmp_name']);
        if (strpos($mime, 'image/') !== 0) {
            header('Location: admin.php?status=error&msg=' . urlencode('अवैध फ़ाइल प्रकार: यह इमेज नहीं है।') . '#media-manager');
            exit;
        }

        if (move_uploaded_file($file['tmp_name'], $target)) {
            header('Location: admin.php?status=success&msg=' . urlencode('इमेज सफलतापूर्वक बदल दी गई है! (Image successfully replaced!)') . '#media-manager');
            exit;
        } else {
            header('Location: admin.php?status=error&msg=' . urlencode('फ़ाइल सहेजने में विफल।') . '#media-manager');
            exit;
        }
    }
}

// 5. Upload New Image/GIF
if (isset($_POST['action']) && $_POST['action'] === 'upload_new') {
    if (isset($_POST['folder']) && isset($_POST['filename']) && isset($_FILES['new_file'])) {
        $folder = trim($_POST['folder']);
        $filename = trim($_POST['filename']);
        
        $allowed_folders = ['images/', 'images/benefits/', 'images/herbs/', 'images/reviews/', 'images/steps/'];
        if (!in_array($folder, $allowed_folders)) {
            header('Location: admin.php?status=error&msg=' . urlencode('सुरक्षा त्रुटि: अवैध फ़ोल्डर।') . '#media-manager');
            exit;
        }
        
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $filename);
        if (empty($filename)) {
            header('Location: admin.php?status=error&msg=' . urlencode('कृपया एक वैध फ़ाइल नाम दर्ज करें।') . '#media-manager');
            exit;
        }

        $file = $_FILES['new_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            header('Location: admin.php?status=error&msg=' . urlencode('अपलोड त्रुटि कोड: ' . $file['error']) . '#media-manager');
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            header('Location: admin.php?status=error&msg=' . urlencode('अवैध फ़ाइल प्रकार। केवल JPG, PNG, GIF, WEBP की अनुमति है।') . '#media-manager');
            exit;
        }

        $mime = mime_content_type($file['tmp_name']);
        if (strpos($mime, 'image/') !== 0) {
            header('Location: admin.php?status=error&msg=' . urlencode('अवैध फ़ाइल प्रकार: यह इमेज नहीं है।') . '#media-manager');
            exit;
        }

        $destination = $folder . $filename . '.' . $ext;
        
        if (file_exists($destination)) {
            header('Location: admin.php?status=error&msg=' . urlencode('यह फ़ाइल पहले से मौजूद है। कृपया दूसरा नाम चुनें या इसे बदलें।') . '#media-manager');
            exit;
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            header('Location: admin.php?status=success&msg=' . urlencode('नई फ़ाइल सफलतापूर्वक अपलोड की गई! (New file successfully uploaded!)') . '#media-manager');
            exit;
        } else {
            header('Location: admin.php?status=error&msg=' . urlencode('फ़ाइल सहेजने में विफल।') . '#media-manager');
            exit;
        }
    }
}

// === FETCH DATA & STATISTICS ===
$startDate = '';
$endDate = '';

try {
    $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
    
    if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $startDate = '';
    }
    if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $endDate = '';
    }
    
    $whereClauses = [];
    $params = [];
    
    if ($startDate) {
        $whereClauses[] = "created_at >= :start_date";
        $params[':start_date'] = $startDate . ' 00:00:00';
    }
    if ($endDate) {
        $whereClauses[] = "created_at <= :end_date";
        $params[':end_date'] = $endDate . ' 23:59:59';
    }
    
    $whereSQL = "";
    if (!empty($whereClauses)) {
        $whereSQL = " WHERE " . implode(" AND ", $whereClauses);
    }

    // Fetch filtered records
    $stmt = $pdo->prepare("SELECT * FROM inquiries" . $whereSQL . " ORDER BY id DESC");
    $stmt->execute($params);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total Count (Overall database statistics)
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM inquiries");
    $totalCount = $totalStmt->fetchColumn();

    // Today's Count
    $todayStmt = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE DATE(created_at) = CURDATE()");
    $todayCount = $todayStmt->fetchColumn();

    // Confirmed Count
    $confirmedStmt = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'Confirmed'");
    $confirmedCount = $confirmedStmt->fetchColumn();
} catch (PDOException $e) {
    die("Database Query Failed: " . $e->getMessage());
}

// Define export URL with filters
$exportUrl = "admin.php?action=export";
if (!empty($startDate)) {
    $exportUrl .= "&start_date=" . urlencode($startDate);
}
if (!empty($endDate)) {
    $exportUrl .= "&end_date=" . urlencode($endDate);
}

// === MEDIA MANAGEMENT HELPERS ===
function get_friendly_filesize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

function get_all_images($dir) {
    $result = [];
    if (!is_dir($dir)) return $result;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            $result = array_merge($result, get_all_images($path));
        } else {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $result[] = $path;
            }
        }
    }
    return $result;
}

function get_image_details($path) {
    $size = filesize($path);
    $friendly_size = get_friendly_filesize($size);
    $dimensions = 'N/A';
    $img_info = @getimagesize($path);
    if ($img_info) {
        $dimensions = $img_info[0] . 'x' . $img_info[1];
    }
    return [
        'path' => $path,
        'size' => $friendly_size,
        'dimensions' => $dimensions,
        'ext' => strtolower(pathinfo($path, PATHINFO_EXTENSION))
    ];
}

// Load and categorize all images
$all_images = get_all_images('images');
$media_categories = [
    'hero' => [
        'title' => 'मुख्य बैनर और सामान्य चित्र (Hero & General Images / GIFs)',
        'files' => []
    ],
    'steps' => [
        'title' => 'यह कैसे काम करता है? (Timeline Steps Images)',
        'files' => []
    ],
    'herbs' => [
        'title' => 'जड़ी-बूटियाँ (Herbs Section Images)',
        'files' => []
    ],
    'benefits' => [
        'title' => 'मुख्य लाभ (Benefits Section Images)',
        'files' => []
    ],
    'reviews' => [
        'title' => 'सच्ची समीक्षाएं (Customer Review Avatars)',
        'files' => []
    ]
];

foreach ($all_images as $img) {
    // Normalise slash to forward slash
    $img_norm = str_replace('\\', '/', $img);
    if (strpos($img_norm, 'images/herbs/') === 0) {
        $media_categories['herbs']['files'][] = $img_norm;
    } elseif (strpos($img_norm, 'images/benefits/') === 0 || $img_norm === 'images/benefits_showcase.gif') {
        $media_categories['benefits']['files'][] = $img_norm;
    } elseif (strpos($img_norm, 'images/reviews/') === 0) {
        $media_categories['reviews']['files'][] = $img_norm;
    } elseif (strpos($img_norm, 'images/steps/') === 0) {
        $media_categories['steps']['files'][] = $img_norm;
    } else {
        $media_categories['hero']['files'][] = $img_norm;
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJAS+ — Admin Lead Panel</title>
  <link rel="icon" type="image/png" href="images/logo.png">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --saffron: #E07B2A;
      --saffron-deep: #C4611A;
      --gold: #F5C542;
      --cream: #FFF8EE;
      --dark: #1A0F00;
      --brown: #3D1F00;
      --green: #2D6A4F;
      --green-light: #52B788;
      --white: #FFFFFF;
      --red: #C93B2B;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Noto Sans Devanagari', sans-serif;
      background: var(--cream);
      color: var(--dark);
      padding: 30px 1.5%;
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      flex-wrap: wrap;
      gap: 16px;
      border-bottom: 3px solid var(--saffron);
      padding-bottom: 16px;
    }

    .brand h1 {
      font-family: 'Playfair Display', serif;
      font-size: 28px;
      color: var(--saffron-deep);
    }
    .brand span { color: var(--green); }
    .brand p {
      font-size: 12px;
      color: var(--brown);
      font-weight: 700;
    }

    .header-actions {
      display: flex;
      gap: 12px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 700;
      font-size: 14px;
      text-decoration: none;
      border: none;
      cursor: pointer;
      font-family: inherit;
      transition: transform 0.2s, background 0.2s;
    }
    .btn-primary {
      background: var(--green);
      color: white;
    }
    .btn-primary:hover { background: #224f3b; }
    .btn-secondary {
      background: var(--white);
      color: var(--dark);
      border: 1px solid #e8d5be;
    }
    .btn-secondary:hover { background: #fdfaf6; }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.04);
      border: 1px solid rgba(224,123,42,0.1);
      display: flex;
      flex-direction: column;
    }
    .stat-card .label {
      font-size: 13px;
      color: #9a7050;
      font-weight: 700;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .stat-card .value {
      font-size: 32px;
      font-weight: 800;
      color: var(--dark);
    }
    .stat-card.primary .value { color: var(--saffron-deep); }
    .stat-card.success .value { color: var(--green); }

    /* Leads Table Section */
    .panel-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.05);
      border: 1px solid rgba(224,123,42,0.12);
      overflow: hidden;
    }
    .panel-header {
      padding: 20px 24px;
      border-bottom: 1px solid #e8d5be;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      background: #fffdfb;
    }
    .panel-header h3 {
      font-size: 18px;
      color: var(--brown);
    }
    .search-box input {
      padding: 8px 16px;
      border-radius: 8px;
      border: 1px solid #e8d5be;
      outline: none;
      font-family: inherit;
      font-size: 14px;
      width: 240px;
    }
    .search-box input:focus {
      border-color: var(--saffron);
    }

    .table-wrap {
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 14px;
    }
    th, td {
      padding: 16px 20px;
      border-bottom: 1px solid #fdf6ee;
    }
    th {
      background: #fdf8f4;
      color: #9a7050;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 12px;
      letter-spacing: 0.5px;
    }
    tr:hover { background: #fffdfb; }

    .ord-id { font-weight: 700; color: var(--saffron-deep); }
    .lead-name { font-weight: 700; color: var(--dark); }
    .lead-phone a {
      color: var(--green);
      text-decoration: none;
      font-weight: 700;
    }
    .lead-phone a:hover { text-decoration: underline; }

    /* Badges & Actions */
    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .badge-pending { background: #fff3e0; color: #e65100; }
    .badge-confirmed { background: #e8f5e9; color: #1b5e20; }
    .badge-cancelled { background: #ffebee; color: #c62828; }

    .actions-cell {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .action-btn {
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 700;
      text-decoration: none;
      border: none;
      cursor: pointer;
      font-family: inherit;
      transition: background 0.2s;
    }
    .action-btn-status {
      background: #f0f0f0;
      color: var(--dark);
    }
    .action-btn-status:hover { background: #e0e0e0; }
    .action-btn-delete {
      background: var(--red);
      color: white;
    }
    .action-btn-delete:hover { background: #ab2c1f; }

    .status-select {
      padding: 6px;
      border-radius: 6px;
      border: 1px solid #e8d5be;
      background: white;
      font-family: inherit;
      font-size: 12px;
      font-weight: 600;
      outline: none;
      cursor: pointer;
    }

    @media (max-width: 768px) {
      body { padding: 15px 1%; }
      th, td { padding: 12px 14px; }
    }

    /* Tab Navigation */
    .tab-nav {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }
    .tab-link {
      padding: 12px 24px;
      background: white;
      border: 1px solid rgba(224,123,42,0.15);
      border-radius: 10px;
      color: var(--brown);
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 15px;
    }
    .tab-link.active {
      background: var(--saffron);
      color: white;
      border-color: var(--saffron);
      box-shadow: 0 4px 12px rgba(224,123,42,0.2);
    }
    .tab-link:hover:not(.active) {
      background: #fffdfa;
      border-color: var(--saffron);
    }

    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
      animation: fadeIn 0.4s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Alerts */
    .alert {
      padding: 16px 20px;
      border-radius: 10px;
      margin-bottom: 24px;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .alert-success {
      background: #E8F5E9;
      color: #1B5E20;
      border-left: 5px solid #2E7D32;
    }
    .alert-error {
      background: #FFEBEE;
      color: #C62828;
      border-left: 5px solid #D32F2F;
    }

    /* Media Manager Grid */
    .media-section-title {
      font-size: 18px;
      color: var(--brown);
      margin: 35px 0 15px 0;
      border-left: 4px solid var(--saffron);
      padding-left: 10px;
      font-weight: 700;
    }
    .media-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 20px;
    }
    .media-card {
      background: white;
      border-radius: 12px;
      border: 1px solid rgba(224,123,42,0.1);
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      display: flex;
      flex-direction: column;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .media-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(224,123,42,0.1);
    }
    .media-thumb-container {
      height: 160px;
      background: #f5f5f5;
      background-image: linear-gradient(45deg, #eee 25%, transparent 25%, transparent 75%, #eee 75%, #eee), 
                        linear-gradient(45deg, #eee 25%, white 25%, white 75%, #eee 75%, #eee);
      background-size: 20px 20px;
      background-position: 0 0, 10px 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      padding: 10px;
    }
    .media-thumb-container img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      transition: transform 0.3s;
    }
    .media-card:hover .media-thumb-container img {
      transform: scale(1.05);
    }
    .media-info {
      padding: 12px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 8px;
    }
    .media-filename {
      font-weight: 700;
      color: var(--dark);
      font-size: 13px;
      word-break: break-all;
    }
    .media-meta {
      font-size: 11px;
      color: #8c8c8c;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid #fdf6ee;
      border-bottom: 1px solid #fdf6ee;
      padding: 4px 0;
    }
    .media-ext-badge {
      background: #e8d5be;
      color: var(--brown);
      padding: 2px 6px;
      border-radius: 4px;
      font-weight: 700;
      font-size: 10px;
      text-transform: uppercase;
    }

    /* Upload Styling */
    .upload-btn-wrapper {
      position: relative;
      overflow: hidden;
      display: inline-block;
      width: 100%;
    }
    .upload-btn-wrapper input[type=file] {
      font-size: 100px;
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      cursor: pointer;
    }
    .btn-replace-trigger {
      width: 100%;
      padding: 8px 12px;
      background: var(--cream);
      border: 1px dashed var(--saffron);
      color: var(--saffron-deep);
      border-radius: 6px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    .btn-replace-trigger:hover {
      background: var(--saffron);
      color: white;
      border-style: solid;
    }

    /* Add New Media Form styling */
    .upload-new-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      border: 1px dashed var(--saffron);
      margin-bottom: 30px;
      box-shadow: 0 4px 16px rgba(224,123,42,0.05);
    }
    .upload-new-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      align-items: flex-end;
    }
    .form-group-upload {
      display: flex;
      flex-direction: column;
      gap: 6px;
      text-align: left;
    }
    .form-group-upload label {
      font-size: 13px;
      font-weight: 700;
      color: var(--brown);
    }
    .form-group-upload input, .form-group-upload select {
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #e8d5be;
      font-family: inherit;
      font-size: 13px;
      outline: none;
    }
    .form-group-upload input:focus, .form-group-upload select:focus {
      border-color: var(--saffron);
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header>
  <div class="brand">
    <h1>OJAS <span>+</span> Leads</h1>
    <p>ZAVIORA HEALTHCARE LEAD MANAGEMENT PANEL</p>
  </div>
  <div class="header-actions">
    <a href="<?= $exportUrl ?>" class="btn btn-primary">📥 Export CSV (.csv)</a>
    <a href="index.php" target="_blank" class="btn btn-secondary">🌐 View Site</a>
  </div>
</header>

<!-- STATUS ALERTS -->
<?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
  <div class="alert alert-<?= htmlspecialchars($_GET['status']) === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars($_GET['status']) === 'success' ? '✅' : '❌' ?> 
    <?= htmlspecialchars($_GET['msg']) ?>
  </div>
<?php endif; ?>

<!-- STATS GRID -->
<div class="stats-grid">
  <div class="stat-card primary">
    <div class="label">Total Inquiries (कुल लीड्स)</div>
    <div class="value"><?= $totalCount ?></div>
  </div>
  <div class="stat-card">
    <div class="label">Today's Inquiries (आज की लीड्स)</div>
    <div class="value"><?= $todayCount ?></div>
  </div>
  <div class="stat-card success">
    <div class="label">Confirmed Orders (स्वीकृत)</div>
    <div class="value"><?= $confirmedCount ?></div>
  </div>
  <?php if (!empty($startDate) || !empty($endDate)): ?>
    <div class="stat-card" style="border: 1px solid rgba(45, 106, 79, 0.3); background: #f4faf7;">
      <div class="label" style="color: var(--green);">Filtered Inquiries (फ़िल्टर की गई लीड्स)</div>
      <div class="value" style="color: var(--green);"><?= count($leads) ?></div>
    </div>
  <?php endif; ?>
</div>

<!-- TAB NAVIGATION -->
<div class="tab-nav">
  <a href="#leads-list" class="tab-link active" data-tab="leads-list">📋 Customer Leads (लीड्स)</a>
  <a href="#media-manager" class="tab-link" data-tab="media-manager">🖼️ Media Manager (इमेज & GIF)</a>
</div>

<!-- LEADS TAB CONTENT -->
<div id="leads-list" class="tab-content active">
  <div class="panel-card">
    <div class="panel-header" style="flex-direction: column; align-items: stretch; gap: 16px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h3>📋 Customer Inquiries List</h3>
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="नाम या मोबाइल से खोजें...">
        </div>
      </div>
      
      <!-- Date Filter Form -->
      <form method="GET" action="admin.php" class="filter-form" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; background: #fffcf8; padding: 16px 20px; border-radius: 12px; border: 1px solid rgba(224,123,42,0.15); margin-top: 8px; width: 100%;">
        <div class="filter-group" style="display: flex; flex-direction: column; gap: 6px; min-width: 150px; flex: 1 1 0;">
          <label for="start_date" style="font-size: 13px; font-weight: 700; color: var(--brown);">प्रारंभ तिथि (Start Date)</label>
          <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #e8d5be; font-family: inherit; font-size: 13px; outline: none; background: white; width: 100%;">
        </div>
        <div class="filter-group" style="display: flex; flex-direction: column; gap: 6px; min-width: 150px; flex: 1 1 0;">
          <label for="end_date" style="font-size: 13px; font-weight: 700; color: var(--brown);">अंतिम तिथि (End Date)</label>
          <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #e8d5be; font-family: inherit; font-size: 13px; outline: none; background: white; width: 100%;">
        </div>
        <div class="filter-actions" style="display: flex; gap: 10px; align-items: center; justify-content: flex-start;">
          <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; height: 38px;">🔍 Apply Filter (फ़िल्टर करें)</button>
          <?php if (!empty($startDate) || !empty($endDate)): ?>
            <a href="admin.php" class="btn btn-secondary" style="padding: 10px 20px; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; height: 38px; color: var(--red); border-color: rgba(201,59,43,0.2);">❌ Clear Filter (साफ़ करें)</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
    
    <div class="table-wrap">
      <table id="leadsTable">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Date &amp; Time</th>
            <th>Customer Name</th>
            <th>Mobile Number</th>
            <th>Full Address</th>
            <th>PIN Code</th>
            <th>Platform</th>
            <th>Device</th>
            <th>IP Address</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($leads)): ?>
            <tr>
              <td colspan="11" style="text-align: center; padding: 40px; color: #9a7050;">
                कोई पूछताछ डेटा उपलब्ध नहीं है। (No inquiries found.)
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($leads as $row): ?>
              <tr>
                <td class="ord-id">ORD-<?= $row['id'] ?></td>
                <td style="white-space: nowrap;"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                <td class="lead-name"><?= htmlspecialchars($row['name']) ?></td>
                <td class="lead-phone">
                  <a href="tel:<?= $row['phone'] ?>">📞 <?= htmlspecialchars($row['phone']) ?></a>
                </td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td><code><?= htmlspecialchars($row['pin']) ?></code></td>
                <td><span style="font-weight:600; color:var(--saffron-deep);"><?= htmlspecialchars(isset($row['platform']) ? $row['platform'] : 'Direct / Organic') ?></span></td>
                <td><?= htmlspecialchars(isset($row['device']) ? $row['device'] : 'Desktop/Laptop') ?></td>
                <td><small><code><?= htmlspecialchars(isset($row['ip_address']) ? $row['ip_address'] : 'N/A') ?></code></small></td>
                <td>
                  <span class="badge badge-<?= strtolower($row['status']) ?>">
                    <?= $row['status'] === 'Pending' ? 'Pending (लंबित)' : ($row['status'] === 'Confirmed' ? 'Confirmed' : 'Cancelled') ?>
                  </span>
                </td>
                <td class="actions-cell">
                  <select class="status-select" onchange="changeStatus(<?= $row['id'] ?>, this.value)">
                    <option value="Pending" <?= $row['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Confirmed" <?= $row['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="Cancelled" <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                  </select>
                  <a href="admin.php?action=delete&id=<?= $row['id'] ?>" 
                     class="action-btn action-btn-delete"
                     onclick="return confirm('क्या आप सच में इस लीड को हटाना चाहते हैं?')">🗑️</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MEDIA MANAGER TAB CONTENT -->
<div id="media-manager" class="tab-content">
  <!-- UPLOAD NEW MEDIA CARD -->
  <div class="upload-new-card">
    <h3 style="margin-bottom:15px; color:var(--brown); display:flex; align-items:center; gap:8px;">📤 Upload New Image / GIF</h3>
    <form action="admin.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload_new">
      <div class="upload-new-grid">
        <div class="form-group-upload">
          <label>Destination Folder (फ़ोल्डर)</label>
          <select name="folder" required>
            <option value="images/">images/ (Root Folder)</option>
            <option value="images/steps/">images/steps/ (Timeline Steps)</option>
            <option value="images/benefits/">images/benefits/ (Key Benefits)</option>
            <option value="images/herbs/">images/herbs/ (Herbs Grid)</option>
            <option value="images/reviews/">images/reviews/ (Customer Avatars)</option>
          </select>
        </div>
        <div class="form-group-upload">
          <label>File Name (नाम - e.g. promo_banner)</label>
          <input type="text" name="filename" placeholder="e.g. promo_banner" required pattern="[a-zA-Z0-9_\-]+">
        </div>
        <div class="form-group-upload">
          <label>Choose File (इमेज या GIF चुनें)</label>
          <input type="file" name="new_file" accept=".jpg,.jpeg,.png,.gif,.webp" required>
        </div>
        <div>
          <button type="submit" class="btn btn-primary" style="width:100%; height:40px; justify-content:center; gap:6px;">🚀 Upload File</button>
        </div>
      </div>
    </form>
  </div>

  <!-- CATEGORIZED GALLERY -->
  <?php foreach ($media_categories as $key => $category): ?>
    <?php if (!empty($category['files'])): ?>
      <div class="media-section-title"><?= $category['title'] ?></div>
      <div class="media-grid">
        <?php foreach ($category['files'] as $img): ?>
          <?php $details = get_image_details($img); ?>
          <div class="media-card">
            <div class="media-thumb-container">
              <img src="<?= $img ?>?v=<?= filemtime($img) ?>" alt="<?= basename($img) ?>" loading="lazy">
            </div>
            <div class="media-info">
              <div>
                <div class="media-filename" title="<?= htmlspecialchars($img) ?>"><?= basename($img) ?></div>
                <div style="font-size:10px; color:#a0a0a0; word-break:break-all; margin-top:2px;"><?= htmlspecialchars($img) ?></div>
              </div>
              <div class="media-meta">
                <span><?= $details['dimensions'] ?></span>
                <span><?= $details['size'] ?></span>
                <span class="media-ext-badge"><?= $details['ext'] ?></span>
              </div>
              
              <!-- Replace Form -->
              <form action="admin.php" method="POST" enctype="multipart/form-data" style="margin-top:6px;">
                <input type="hidden" name="action" value="replace_image">
                <input type="hidden" name="target_file" value="<?= htmlspecialchars($img) ?>">
                <div class="upload-btn-wrapper">
                  <button type="button" class="btn-replace-trigger">🔄 Replace File</button>
                  <input type="file" name="replacement_file" accept=".jpg,.jpeg,.png,.gif,.webp" required onchange="this.form.submit()">
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

<script>
  // Simple search filtering
  document.getElementById('searchInput').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    const rows = document.querySelectorAll('#leadsTable tbody tr');
    
    rows.forEach(row => {
      // Skip empty state row if it exists
      if (row.cells.length < 8) return;
      
      const name = row.cells[2].textContent.toLowerCase();
      const phone = row.cells[3].textContent.toLowerCase();
      const address = row.cells[4].textContent.toLowerCase();
      
      if (name.includes(query) || phone.includes(query) || address.includes(query)) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  });

  // Change Status Redirect
  function changeStatus(id, value) {
    window.location.href = `admin.php?action=status&id=${id}&value=${value}`;
  }

  // Tab Switching Logic
  const tabs = document.querySelectorAll('.tab-link');
  const contents = document.querySelectorAll('.tab-content');

  function switchTab(tabId) {
    tabs.forEach(t => t.classList.remove('active'));
    contents.forEach(c => c.classList.remove('active'));

    const activeTab = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
    const activeContent = document.getElementById(tabId);

    if (activeTab && activeContent) {
      activeTab.classList.add('active');
      activeContent.classList.add('active');
      history.replaceState(null, null, '#' + tabId);
    }
  }

  tabs.forEach(tab => {
    tab.addEventListener('click', (e) => {
      e.preventDefault();
      const tabId = tab.getAttribute('data-tab');
      switchTab(tabId);
    });
  });

  // Check Hash on Load
  window.addEventListener('load', () => {
    const hash = window.location.hash.substring(1);
    if (hash === 'media-manager' || hash === 'leads-list') {
      switchTab(hash);
    } else {
      switchTab('leads-list');
    }
  });
</script>
</body>
</html>
