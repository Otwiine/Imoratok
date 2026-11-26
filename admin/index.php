<?php
/**
 * Imoratok Safaris — Protected Admin Inquiries Dashboard
 */

declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/../config.php';

// Authentication check
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pdo = get_db_connection();
$notice = '';
$error = '';

// --------------------------------------------------------------------------
// 1. Handle Actions (CSRF Protected)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            exit;
        }
        $error = 'Security check failed. Please refresh and try again.';
    } elseif ($action === 'mark_read' && $id) {
        $stmt = $pdo->prepare("UPDATE inquiries SET status = 'read' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => true]);
            exit;
        }
        $notice = 'Inquiry marked as read.';
    } elseif ($action === 'toggle_status' && $id) {
        $new_status = ($_POST['current_status'] ?? '') === 'read' ? 'unread' : 'read';
        $stmt = $pdo->prepare("UPDATE inquiries SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $id]);
        $notice = 'Inquiry status updated.';
    } elseif ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $notice = 'Inquiry deleted successfully.';
    }
}

// --------------------------------------------------------------------------
// 2. CSV Export
// --------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="imoratok_inquiries_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Date/Time (UTC)', 'Name', 'Email', 'Status', 'IP Address', 'Message']);

    $stmt = $pdo->query("SELECT id, created_at, name, email, status, ip_address, message FROM inquiries ORDER BY created_at DESC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $row['name'],
            $row['email'],
            ucfirst($row['status']),
            $row['ip_address'],
            $row['message']
        ]);
    }
    fclose($output);
    exit;
}

// --------------------------------------------------------------------------
// 3. Fetch Dashboard Stats & List
// --------------------------------------------------------------------------
$stats = [
    'total' => 0,
    'unread' => 0,
    'today' => 0
];

$total_row = $pdo->query("SELECT COUNT(*) as c FROM inquiries")->fetch();
$stats['total'] = (int)($total_row['c'] ?? 0);

$unread_row = $pdo->query("SELECT COUNT(*) as c FROM inquiries WHERE status = 'unread'")->fetch();
$stats['unread'] = (int)($unread_row['c'] ?? 0);

$today_row = $pdo->query("SELECT COUNT(*) as c FROM inquiries WHERE date(created_at) = date('now')")->fetch();
$stats['today'] = (int)($today_row['c'] ?? 0);

$filter = $_GET['filter'] ?? 'all';
$query = "SELECT * FROM inquiries ";
if ($filter === 'unread') {
    $query .= "WHERE status = 'unread' ";
} elseif ($filter === 'read') {
    $query .= "WHERE status = 'read' ";
}
$query .= "ORDER BY created_at DESC LIMIT 200";

$inquiries = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inquiries Dashboard — Imoratok Safaris</title>
  <link rel="icon" href="../favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
  <style>
    :root {
      --color-primary: #1b382b;
      --color-primary-dark: #11221a;
      --color-gold: #e5a742;
      --color-gold-hover: #f1b34e;
      --color-bg: #0d1812;
      --color-surface: #15271e;
      --color-surface-hover: #1c3429;
      --color-border: #243f31;
      --color-text: #ffffff;
      --color-text-muted: #95ab9f;
      --color-badge-unread: #d97706;
      --color-badge-read: #059669;
      --color-danger: #ef4444;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--color-bg);
      color: var(--color-text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .navbar {
      background: var(--color-surface);
      border-bottom: 1px solid var(--color-border);
      padding: 16px 28px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .nav-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: #fff;
    }
    .brand-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.35rem;
      font-weight: 700;
    }
    .brand-tag {
      font-size: 0.76rem;
      background: rgba(229, 167, 66, 0.15);
      color: var(--color-gold);
      border: 1px solid var(--color-gold);
      padding: 2px 8px;
      border-radius: 4px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
    }
    .nav-actions {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      font-size: 0.88rem;
      font-weight: 600;
      border-radius: 6px;
      border: 1px solid transparent;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-outline {
      background: transparent;
      border-color: var(--color-border);
      color: var(--color-text-muted);
    }
    .btn-outline:hover {
      color: #fff;
      border-color: var(--color-gold);
    }
    .btn-gold {
      background: var(--color-gold);
      color: #11221a;
      border-color: var(--color-gold);
    }
    .btn-gold:hover {
      background: var(--color-gold-hover);
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 28px 24px 60px;
      width: 100%;
      flex: 1;
    }
    /* Stats Bar */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: var(--color-surface);
      border: 1px solid var(--color-border);
      border-radius: 8px;
      padding: 18px 20px;
    }
    .stat-label {
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--color-text-muted);
      margin-bottom: 6px;
    }
    .stat-val {
      font-size: 1.8rem;
      font-weight: 700;
      color: #ffffff;
    }
    .stat-val.gold { color: var(--color-gold); }
    /* Controls Bar */
    .controls-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 18px;
    }
    .filter-pills {
      display: flex;
      gap: 8px;
    }
    .filter-pill {
      padding: 6px 14px;
      font-size: 0.82rem;
      font-weight: 600;
      border-radius: 20px;
      text-decoration: none;
      color: var(--color-text-muted);
      background: var(--color-surface);
      border: 1px solid var(--color-border);
      transition: all 0.2s;
    }
    .filter-pill.active, .filter-pill:hover {
      color: #fff;
      border-color: var(--color-gold);
      background: rgba(229, 167, 66, 0.12);
    }
    /* Table */
    .table-card {
      background: var(--color-surface);
      border: 1px solid var(--color-border);
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    }
    .inquiries-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }
    .inquiries-table th {
      background: #101e17;
      color: var(--color-text-muted);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      font-weight: 600;
      padding: 12px 18px;
      border-bottom: 1px solid var(--color-border);
    }
    .inquiries-table td {
      padding: 14px 18px;
      font-size: 0.9rem;
      border-bottom: 1px solid var(--color-border);
      vertical-align: middle;
    }
    .inquiries-table tr:hover td {
      background: var(--color-surface-hover);
    }
    .status-badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.74rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .badge-unread {
      background: rgba(217, 119, 6, 0.2);
      color: #fbbf24;
      border: 1px solid rgba(251, 191, 36, 0.4);
    }
    .badge-read {
      background: rgba(5, 150, 105, 0.2);
      color: #34d399;
      border: 1px solid rgba(52, 211, 153, 0.3);
    }
    .guest-name {
      font-weight: 600;
      color: #fff;
    }
    .guest-email {
      color: var(--color-text-muted);
      font-size: 0.82rem;
      text-decoration: none;
    }
    .guest-email:hover {
      color: var(--color-gold);
    }
    .message-preview {
      max-width: 320px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: #d1ded7;
      font-size: 0.88rem;
    }
    .date-col {
      white-space: nowrap;
      font-size: 0.8rem;
      color: var(--color-text-muted);
    }
    .action-btns {
      display: flex;
      gap: 6px;
      justify-content: flex-end;
    }
    .btn-sm {
      padding: 5px 10px;
      font-size: 0.78rem;
    }
    .btn-danger {
      background: transparent;
      border-color: rgba(239, 68, 68, 0.4);
      color: var(--color-danger);
    }
    .btn-danger:hover {
      background: rgba(239, 68, 68, 0.15);
      border-color: var(--color-danger);
    }
    /* Modal */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(4px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-backdrop.show { display: flex; }
    .modal-box {
      background: var(--color-surface);
      border: 1px solid var(--color-border);
      border-radius: 10px;
      width: 100%;
      max-width: 600px;
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    }
    .modal-header {
      padding: 18px 24px;
      border-bottom: 1px solid var(--color-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-body {
      padding: 24px;
      overflow-y: auto;
      font-size: 0.95rem;
      line-height: 1.6;
    }
    .modal-footer {
      padding: 14px 24px;
      border-top: 1px solid var(--color-border);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    .message-full {
      background: #0f1c15;
      border: 1px solid var(--color-border);
      border-radius: 6px;
      padding: 16px;
      white-space: pre-wrap;
      font-family: inherit;
      margin-top: 12px;
      color: #e5eee8;
    }
    .detail-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
      font-size: 0.88rem;
    }
    .detail-label { color: var(--color-text-muted); }
    .detail-val { font-weight: 600; color: #fff; }
    .empty-state {
      padding: 50px 20px;
      text-align: center;
      color: var(--color-text-muted);
    }
    .alert-banner {
      background: rgba(5, 150, 105, 0.15);
      border: 1px solid var(--color-badge-read);
      color: #34d399;
      padding: 10px 16px;
      border-radius: 6px;
      margin-bottom: 18px;
      font-size: 0.88rem;
    }
  </style>
</head>
<body>
  <header class="navbar">
    <a href="index.php" class="nav-brand">
      <span class="brand-title">Imoratok Safaris</span>
      <span class="brand-tag">Dashboard</span>
    </a>
    <div class="nav-actions">
      <a href="../index.html" class="btn btn-outline" target="_blank">View Site ↗</a>
      <a href="logout.php" class="btn btn-outline">Log Out</a>
    </div>
  </header>

  <main class="container">
    <?php if ($notice): ?>
      <div class="alert-banner">✓ <?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <section class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">Total Submissions</div>
        <div class="stat-val" id="statTotal"><?= $stats['total'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Unread Inquiries</div>
        <div class="stat-val gold" id="statUnread"><?= $stats['unread'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Received Today</div>
        <div class="stat-val"><?= $stats['today'] ?></div>
      </div>
    </section>

    <div class="controls-bar">
      <div class="filter-pills">
        <a href="index.php?filter=all" class="filter-pill <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $stats['total'] ?>)</a>
        <a href="index.php?filter=unread" id="filterPillUnread" class="filter-pill <?= $filter === 'unread' ? 'active' : '' ?>">Unread (<?= $stats['unread'] ?>)</a>
        <a href="index.php?filter=read" id="filterPillRead" class="filter-pill <?= $filter === 'read' ? 'active' : '' ?>">Read (<?= $stats['total'] - $stats['unread'] ?>)</a>
      </div>

      <div>
        <a href="index.php?action=export_csv" class="btn btn-outline btn-sm">📥 Export CSV</a>
      </div>
    </div>

    <div class="table-card">
      <?php if (empty($inquiries)): ?>
        <div class="empty-state">
          <p>No inquiries found under this filter.</p>
        </div>
      <?php else: ?>
        <table class="inquiries-table">
          <thead>
            <tr>
              <th>Status</th>
              <th>Guest Name & Email</th>
              <th>Dream Safari Request</th>
              <th>Date Received</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($inquiries as $inq): ?>
              <tr id="inquiry-row-<?= $inq['id'] ?>">
                <td>
                  <span id="status-badge-<?= $inq['id'] ?>" class="status-badge <?= $inq['status'] === 'unread' ? 'badge-unread' : 'badge-read' ?>">
                    <?= htmlspecialchars($inq['status'], ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <div class="guest-name"><?= htmlspecialchars($inq['name'], ENT_QUOTES, 'UTF-8') ?></div>
                  <a href="mailto:<?= htmlspecialchars($inq['email'], ENT_QUOTES, 'UTF-8') ?>" class="guest-email">
                    <?= htmlspecialchars($inq['email'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </td>
                <td>
                  <div class="message-preview">
                    <?= htmlspecialchars($inq['message'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </td>
                <td class="date-col">
                  <?= htmlspecialchars(date('M j, Y — g:i A', strtotime($inq['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td>
                  <div class="action-btns">
                    <button type="button" class="btn btn-outline btn-sm"
                      onclick="openModal(<?= htmlspecialchars(json_encode($inq), ENT_QUOTES, 'UTF-8') ?>)">
                      View
                    </button>
                    
                    <form method="POST" action="index.php<?= $filter !== 'all' ? '?filter=' . urlencode($filter) : '' ?>" style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="id" value="<?= $inq['id'] ?>">
                      <input type="hidden" id="current-status-<?= $inq['id'] ?>" name="current_status" value="<?= $inq['status'] ?>">
                      <button type="submit" id="toggle-btn-<?= $inq['id'] ?>" class="btn btn-outline btn-sm" title="Toggle Read/Unread">
                        <?= $inq['status'] === 'read' ? 'Mark Unread' : 'Mark Read' ?>
                      </button>
                    </form>

                    <form method="POST" action="index.php<?= $filter !== 'all' ? '?filter=' . urlencode($filter) : '' ?>" style="display:inline;" onsubmit="return confirm('Permanently delete this inquiry?');">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $inq['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm" title="Delete">✕</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </main>

  <!-- Detail Modal -->
  <div class="modal-backdrop" id="inquiryModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3 id="modalName" style="font-family: 'Playfair Display', serif; font-size: 1.25rem;">Inquiry Details</h3>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal()">✕</button>
      </div>
      <div class="modal-body">
        <div class="detail-row">
          <span class="detail-label">Email:</span>
          <span class="detail-val"><a id="modalEmailLink" href="#" style="color: var(--color-gold); text-decoration: none;"></a></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Received:</span>
          <span class="detail-val" id="modalDate"></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">IP Address:</span>
          <span class="detail-val" id="modalIp"></span>
        </div>
        <div style="margin-top: 14px;">
          <span class="detail-label">Message:</span>
          <div class="message-full" id="modalMessage"></div>
        </div>
      </div>
      <div class="modal-footer">
        <a id="modalReplyBtn" href="<?= htmlspecialchars(defined('WEBMAIL_LOGIN_URL') ? WEBMAIL_LOGIN_URL : 'https://imoratoksafaris.com/webmail', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-gold">Open Webmail</a>
        <button type="button" class="btn btn-outline" onclick="closeModal()">Close</button>
      </div>
    </div>
  </div>

  <script>
    function openModal(data) {
      document.getElementById('modalName').textContent = data.name;
      const emailLink = document.getElementById('modalEmailLink');
      emailLink.textContent = data.email;
      emailLink.href = 'mailto:' + data.email;
      document.getElementById('modalDate').textContent = data.created_at;
      document.getElementById('modalIp').textContent = data.ip_address || 'Not recorded';
      document.getElementById('modalMessage').textContent = data.message;

      document.getElementById('inquiryModal').classList.add('show');

      // Auto mark as read if currently unread
      if (data.status === 'unread') {
        markAsRead(data.id);
        data.status = 'read';
      }
    }

    async function markAsRead(id) {
      const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';
      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      formData.append('action', 'mark_read');
      formData.append('id', id);

      try {
        const res = await fetch('index.php', {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
          const badge = document.getElementById('status-badge-' + id);
          if (badge) {
            badge.className = 'status-badge badge-read';
            badge.textContent = 'read';
          }
          const toggleBtn = document.getElementById('toggle-btn-' + id);
          if (toggleBtn) toggleBtn.textContent = 'Mark Unread';
          const currentStatusInput = document.getElementById('current-status-' + id);
          if (currentStatusInput) currentStatusInput.value = 'read';

          const unreadStat = document.getElementById('statUnread');
          if (unreadStat) {
            const count = Math.max(0, parseInt(unreadStat.textContent.trim(), 10) - 1);
            unreadStat.textContent = count;
            const pillUnread = document.getElementById('filterPillUnread');
            if (pillUnread) pillUnread.textContent = 'Unread (' + count + ')';
          }
        }
      } catch (err) {
        console.error('Error auto-marking read:', err);
      }
    }

    function closeModal() {
      document.getElementById('inquiryModal').classList.remove('show');
    }

    window.onclick = function(e) {
      const modal = document.getElementById('inquiryModal');
      if (e.target === modal) {
        closeModal();
      }
    };
  </script>
</body>
</html>
