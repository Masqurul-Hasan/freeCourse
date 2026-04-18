<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$page_title = 'Activity Logs';
$meta_description = 'Admin activity logs';

$search = trim($_GET['search'] ?? '');
$actionFilter = trim($_GET['action'] ?? '');

/* =========================================================
   STATS
   ========================================================= */
$totalLogs = (int) $pdo->query("SELECT COUNT(*) FROM admin_logs")->fetchColumn();

$stmt = $pdo->query("
    SELECT COUNT(DISTINCT admin_id)
    FROM admin_logs
    WHERE admin_id IS NOT NULL
");
$totalAdminsActive = (int) $stmt->fetchColumn();

/* =========================================================
   LOG QUERY
   ========================================================= */
$sql = "
    SELECT 
        al.id,
        al.admin_id,
        al.action,
        al.target_id,
        al.created_at,
        a.name AS admin_name,
        a.email AS admin_email
    FROM admin_logs al
    LEFT JOIN admins a ON a.id = al.admin_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        al.action LIKE ?
        OR CAST(al.target_id AS CHAR) LIKE ?
        OR a.name LIKE ?
        OR a.email LIKE ?
    )";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($actionFilter !== '') {
    $sql .= " AND al.action = ?";
    $params[] = $actionFilter;
}

$sql .= " ORDER BY al.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   ACTION LIST FOR FILTER
   ========================================================= */
$actionStmt = $pdo->query("
    SELECT DISTINCT action
    FROM admin_logs
    WHERE action IS NOT NULL AND action != ''
    ORDER BY action ASC
");
$actionList = $actionStmt->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/partials/head.php';
include __DIR__ . '/../includes/partials/admin-header.php';
?>

<style>
.activity-shell {
    padding: 28px 0 48px;
}

.activity-hero {
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid #e8eef6;
    border-radius: 28px;
    padding: 30px;
    margin-bottom: 22px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
}

.activity-hero h1 {
    margin: 0 0 8px;
    font-size: clamp(30px, 3vw, 42px);
    line-height: 1.08;
    color: #0f172a;
    font-weight: 900;
}

.activity-hero p {
    margin: 0;
    color: #64748b;
    font-size: 15px;
    line-height: 1.7;
}

.activity-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}

.activity-stat-card {
    background: #fff;
    border: 1px solid #e8eef6;
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.04);
}

.activity-stat-label {
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 10px;
}

.activity-stat-value {
    font-size: clamp(26px, 2.6vw, 34px);
    line-height: 1.05;
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 8px;
}

.activity-stat-note {
    font-size: 14px;
    color: #64748b;
}

.activity-card {
    background: #fff;
    border: 1px solid #e8eef6;
    border-radius: 28px;
    padding: 24px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
}

.activity-filter-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}

.activity-input,
.activity-select {
    width: 100%;
    min-height: 52px;
    border-radius: 14px;
    border: 1px solid #dbe4ee;
    background: #fff;
    padding: 0 16px;
    font-size: 14px;
    color: #0f172a;
    outline: none;
}

.activity-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.activity-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0 18px;
    border-radius: 14px;
    font-weight: 800;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.activity-btn-primary {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
}

.activity-btn-light {
    background: #fff;
    color: #0f172a;
    border: 1px solid #dbe4ee;
}

.activity-table-wrap {
    overflow-x: auto;
    border: 1px solid #e8eef6;
    border-radius: 20px;
}

.activity-table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
    background: #fff;
}

.activity-table thead th {
    background: #f8fbff;
    padding: 16px 18px;
    text-align: left;
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
    border-bottom: 1px solid #e8eef6;
}

.activity-table tbody td {
    padding: 18px;
    font-size: 14px;
    color: #0f172a;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
}

.activity-table tbody tr:last-child td {
    border-bottom: none;
}

.activity-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 800;
}

.activity-muted {
    color: #64748b;
}

.activity-empty {
    text-align: center;
    padding: 54px 20px;
    color: #64748b;
    border: 1px dashed #d9e3ee;
    border-radius: 20px;
    background: linear-gradient(180deg, #fcfdff 0%, #f8fbff 100%);
}

@media (max-width: 800px) {
    .activity-stats,
    .activity-filter-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="activity-shell">
    <div class="container">

        <section class="activity-hero">
            <h1>Admin Activity Logs</h1>
            <p>Admin panel থেকে করা গুরুত্বপূর্ণ action history এখান থেকে দেখতে পারবেন।</p>
        </section>

        <section class="activity-stats">
            <div class="activity-stat-card">
                <div class="activity-stat-label">Total Logs</div>
                <div class="activity-stat-value"><?= $totalLogs; ?></div>
                <div class="activity-stat-note">সব admin activity entry</div>
            </div>

            <div class="activity-stat-card">
                <div class="activity-stat-label">Active Admins</div>
                <div class="activity-stat-value"><?= $totalAdminsActive; ?></div>
                <div class="activity-stat-note">যারা কোনো action নিয়েছে</div>
            </div>
        </section>

        <section class="activity-card">
            <form method="GET">
                <div class="activity-filter-grid">
                    <input
                        type="text"
                        name="search"
                        class="activity-input"
                        placeholder="action / target id / admin name / email"
                        value="<?= e($search); ?>"
                    >

                    <select name="action" class="activity-select">
                        <option value="">সব Action</option>
                        <?php foreach ($actionList as $actionItem): ?>
                            <option value="<?= e($actionItem); ?>" <?= $actionFilter === $actionItem ? 'selected' : ''; ?>>
                                <?= e($actionItem); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="activity-actions">
                    <button type="submit" class="activity-btn activity-btn-primary">Filter করুন</button>
                    <a href="<?= SITE_URL; ?>/admin/activity-logs.php" class="activity-btn activity-btn-light">Reset</a>
                </div>
            </form>

            <?php if (empty($logs)): ?>
                <div class="activity-empty">কোনো activity log পাওয়া যায়নি।</div>
            <?php else: ?>
                <div class="activity-table-wrap">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Target ID</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $index => $log): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td>
                                        <strong><?= e($log['admin_name'] ?: 'Unknown Admin'); ?></strong><br>
                                        <span class="activity-muted"><?= e($log['admin_email'] ?: 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <span class="activity-badge"><?= e($log['action']); ?></span>
                                    </td>
                                    <td><?= e((string)($log['target_id'] ?? 'N/A')); ?></td>
                                    <td class="activity-muted"><?= e($log['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php include __DIR__ . '/../includes/partials/admin-footer.php'; ?>