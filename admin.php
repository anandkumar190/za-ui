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
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ojas_leads_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for proper excel encoding of Hindi names
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Order ID', 'Date & Time', 'Name', 'Phone', 'Address', 'PIN Code', 'Status']);
    
    $stmt = $pdo->query("SELECT * FROM inquiries ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            'ORD-' . $row['id'],
            $row['created_at'],
            $row['name'],
            $row['phone'],
            $row['address'],
            $row['pin'],
            $row['status']
        ]);
    }
    fclose($output);
    exit;
}

// === FETCH DATA & STATISTICS ===
try {
    // Fetch all records
    $stmt = $pdo->query("SELECT * FROM inquiries ORDER BY id DESC");
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total Count
    $totalCount = count($leads);

    // Today's Count
    $todayStmt = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE DATE(created_at) = CURDATE()");
    $todayCount = $todayStmt->fetchColumn();

    // Confirmed Count
    $confirmedStmt = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'Confirmed'");
    $confirmedCount = $confirmedStmt->fetchColumn();
} catch (PDOException $e) {
    die("Database Query Failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJAS+ — Admin Lead Panel</title>
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
      padding: 30px 5%;
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
      body { padding: 15px 3%; }
      th, td { padding: 12px 14px; }
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
    <a href="admin.php?action=export" class="btn btn-primary">📥 Export CSV (.csv)</a>
    <a href="index.php" target="_blank" class="btn btn-secondary">🌐 View Site</a>
  </div>
</header>

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
</div>

<!-- PANEL CARD -->
<div class="panel-card">
  <div class="panel-header">
    <h3>📋 Customer Inquiries List</h3>
    <div class="search-box">
      <input type="text" id="searchInput" placeholder="नाम या मोबाइल से खोजें...">
    </div>
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
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leads)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 40px; color: #9a7050;">
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
</script>
</body>
</html>
