<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

$db = new Database();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

$sql = "SELECT l.*, u.username 
       FROM admin_logs l 
       JOIN users u ON l.user_id = u.id";
       
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(u.username LIKE :search OR l.action LIKE :search OR l.table_affected LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($action)) {
    $conditions[] = "l.action = :action";
    $params[':action'] = $action;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY l.created_at DESC";

$db->query($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$logs = $db->resultSet();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Logs</h1>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="action" class="form-label">Action</label>
                            <select class="form-select" id="action" name="action">
                                <option value="">All Actions</option>
                                <option value="create" <?php echo $action === 'create' ? 'selected' : ''; ?>>Create</option>
                                <option value="update" <?php echo $action === 'update' ? 'selected' : ''; ?>>Update</option>
                                <option value="delete" <?php echo $action === 'delete' ? 'selected' : ''; ?>>Delete</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>Record ID</th>
                            <th>Date</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log->id; ?></td>
                                    <td><?php echo htmlspecialchars($log->username); ?></td>
                                    <td><?php echo ucfirst($log->action); ?></td>
                                    <td><?php echo $log->table_affected ? htmlspecialchars($log->table_affected) : 'N/A'; ?></td>
                                    <td><?php echo $log->record_id ?: 'N/A'; ?></td>
                                    <td><?php echo date('M j, Y g:i a', strtotime($log->created_at)); ?></td>
                                    <td><?php echo $log->ip_address ?: 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#filterForm').submit(function(e) {
        e.preventDefault();
        const search = $('#search').val();
        const action = $('#action').val();
        
        let queryParams = [];
        if (search) queryParams.push(`search=${encodeURIComponent(search)}`);
        if (action) queryParams.push(`action=${action}`);
        
        window.location.href = `<?php echo BASE_URL; ?>admin/reports/logs.php?${queryParams.join('&')}`;
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>