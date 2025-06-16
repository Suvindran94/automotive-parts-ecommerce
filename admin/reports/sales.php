<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth_check.php';

$db = new Database();

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$group_by = isset($_GET['group_by']) ? $_GET['group_by'] : 'day';

$db->query("SELECT 
           DATE(created_at) as date,
           COUNT(*) as order_count,
           SUM(total_amount) as total_sales
           FROM orders 
           WHERE created_at BETWEEN :start_date AND :end_date + INTERVAL 1 DAY
           GROUP BY DATE(created_at)
           ORDER BY DATE(created_at)");
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$daily_sales = $db->resultSet();

$db->query("SELECT 
           DATE_FORMAT(created_at, '%Y-%m') as month,
           COUNT(*) as order_count,
           SUM(total_amount) as total_sales
           FROM orders 
           WHERE created_at BETWEEN :start_date AND :end_date + INTERVAL 1 DAY
           GROUP BY DATE_FORMAT(created_at, '%Y-%m')
           ORDER BY DATE_FORMAT(created_at, '%Y-%m')");
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$monthly_sales = $db->resultSet();

$db->query("SELECT 
           c.name as category,
           SUM(oi.quantity) as quantity_sold,
           SUM(oi.quantity * oi.price) as total_sales
           FROM order_items oi
           JOIN products p ON oi.product_id = p.id
           LEFT JOIN categories c ON p.category_id = c.id
           JOIN orders o ON oi.order_id = o.id
           WHERE o.created_at BETWEEN :start_date AND :end_date + INTERVAL 1 DAY
           GROUP BY c.name
           ORDER BY total_sales DESC");
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$category_sales = $db->resultSet();

$db->query("SELECT 
           SUM(total_amount) as total_sales,
           COUNT(*) as order_count,
           AVG(total_amount) as avg_order_value
           FROM orders 
           WHERE created_at BETWEEN :start_date AND :end_date + INTERVAL 1 DAY");
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$summary = $db->single();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Sales Reports</h1>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="group_by" class="form-label">Group By</label>
                            <select class="form-select" id="group_by" name="group_by">
                                <option value="day" <?php echo $group_by === 'day' ? 'selected' : ''; ?>>Daily</option>
                                <option value="month" <?php echo $group_by === 'month' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <p class="card-text h3">RM <?php echo number_format($summary->total_sales ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Total Orders</h5>
                            <p class="card-text h3"><?php echo $summary->order_count ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Avg. Order Value</h5>
                            <p class="card-text h3">RM <?php echo number_format($summary->avg_order_value ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Sales Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Sales by Date</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Orders</th>
                                            <th>Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($group_by === 'day'): ?>
                                            <?php foreach ($daily_sales as $sale): ?>
                                                <tr>
                                                    <td><?php echo date('M j, Y', strtotime($sale->date)); ?></td>
                                                    <td><?php echo $sale->order_count; ?></td>
                                                    <td>RM <?php echo number_format($sale->total_sales, 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?php foreach ($monthly_sales as $sale): ?>
                                                <tr>
                                                    <td><?php echo date('F Y', strtotime($sale->month . '-01')); ?></td>
                                                    <td><?php echo $sale->order_count; ?></td>
                                                    <td>RM <?php echo number_format($sale->total_sales, 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Sales by Category</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Quantity Sold</th>
                                            <th>Total Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($category_sales as $sale): ?>
                                            <tr>
                                                <td><?php echo $sale->category ? htmlspecialchars($sale->category) : 'Uncategorized'; ?></td>
                                                <td><?php echo $sale->quantity_sold; ?></td>
                                                <td>RM <?php echo number_format($sale->total_sales, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    $('#filterForm').submit(function(e) {
        e.preventDefault();
        const start_date = $('#start_date').val();
        const end_date = $('#end_date').val();
        const group_by = $('#group_by').val();
        
        window.location.href = `<?php echo BASE_URL; ?>admin/reports/sales.php?start_date=${start_date}&end_date=${end_date}&group_by=${group_by}`;
    });
    
    const salesData = <?php echo json_encode($group_by === 'day' ? $daily_sales : $monthly_sales); ?>;
    
    const labels = salesData.map(item => 
        <?php echo $group_by === 'day' ? "new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })" : "new Date(item.month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' })"; ?>
    );
    
    const sales = salesData.map(item => item.total_sales);
    
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales (RM)',
                data: sales,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'RM ' + context.raw.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>