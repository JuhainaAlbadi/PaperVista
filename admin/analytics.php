<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = "Analytics";

// Get date range (default: last 30 days)
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

// Total statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// New users this period
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)");
$stats['new_users'] = $result->fetch_assoc()['count'];

// Total papers
$result = $conn->query("SELECT COUNT(*) as count FROM papers");
$stats['total_papers'] = $result->fetch_assoc()['count'];

// Papers this period
$result = $conn->query("SELECT COUNT(*) as count FROM papers WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)");
$stats['new_papers'] = $result->fetch_assoc()['count'];

// Total summaries
$result = $conn->query("SELECT COUNT(*) as count FROM summaries");
$stats['total_summaries'] = $result->fetch_assoc()['count'];

// Summaries this period
$result = $conn->query("SELECT COUNT(*) as count FROM summaries WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)");
$stats['new_summaries'] = $result->fetch_assoc()['count'];

// Average summaries per user
$stats['avg_summaries_per_user'] = $stats['total_users'] > 0 ? round($stats['total_summaries'] / $stats['total_users'], 2) : 0;

// Get papers by type
$paper_types = [];
$result = $conn->query("
    SELECT 
        UPPER(SUBSTRING_INDEX(file_path, '.', -1)) as file_type,
        COUNT(*) as count
    FROM papers
    GROUP BY UPPER(SUBSTRING_INDEX(file_path, '.', -1))
    ORDER BY count DESC
");
while ($row = $result->fetch_assoc()) {
    $paper_types[] = $row;
}

// Get summaries by type
$summary_types = [];
$result = $conn->query("
    SELECT summary_type, COUNT(*) as count
    FROM summaries
    GROUP BY summary_type
    ORDER BY count DESC
");
while ($row = $result->fetch_assoc()) {
    $summary_types[] = $row;
}

// Get top users by papers
$top_users_papers = [];
$result = $conn->query("
    SELECT u.first_name, u.last_name, u.email, COUNT(p.id) as paper_count
    FROM users u
    LEFT JOIN papers p ON u.id = p.uploaded_by
    GROUP BY u.id
    ORDER BY paper_count DESC
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $top_users_papers[] = $row;
}

// Get top users by summaries
$top_users_summaries = [];
$result = $conn->query("
    SELECT u.first_name, u.last_name, u.email, COUNT(s.id) as summary_count
    FROM users u
    LEFT JOIN summaries s ON u.id = s.user_id
    GROUP BY u.id
    ORDER BY summary_count DESC
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $top_users_summaries[] = $row;
}

// Get activity by day (last 14 days)
$daily_activity = [];
$result = $conn->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM papers
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
while ($row = $result->fetch_assoc()) {
    $daily_activity[$row['date']] = $row['count'];
}

// Get summary activity by day
$daily_summaries = [];
$result = $conn->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM summaries
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
while ($row = $result->fetch_assoc()) {
    $daily_summaries[$row['date']] = $row['count'];
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold">Analytics Dashboard</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Admin</a></li>
                    <li class="breadcrumb-item active">Analytics</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <a href="?days=7" class="btn btn-sm btn-<?php echo $days == 7 ? 'primary' : 'outline-primary'; ?>">7 Days</a>
                <a href="?days=30" class="btn btn-sm btn-<?php echo $days == 30 ? 'primary' : 'outline-primary'; ?>">30 Days</a>
                <a href="?days=90" class="btn btn-sm btn-<?php echo $days == 90 ? 'primary' : 'outline-primary'; ?>">90 Days</a>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Users</h6>
                            <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_users']); ?></h2>
                            <small class="text-success">+<?php echo $stats['new_users']; ?> in last <?php echo $days; ?> days</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Papers</h6>
                            <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_papers']); ?></h2>
                            <small class="text-success">+<?php echo $stats['new_papers']; ?> in last <?php echo $days; ?> days</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-file-alt fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Summaries</h6>
                            <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_summaries']); ?></h2>
                            <small class="text-success">+<?php echo $stats['new_summaries']; ?> in last <?php echo $days; ?> days</small>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="fas fa-magic fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Avg. Summaries/User</h6>
                            <h2 class="fw-bold mb-0"><?php echo $stats['avg_summaries_per_user']; ?></h2>
                            <small class="text-muted">Per active user</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-chart-line fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <!-- Paper Types -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0 fw-bold">Papers by File Type</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>File Type</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paper_types as $type): ?>
                                <tr>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($type['file_type']); ?></span></td>
                                    <td><?php echo number_format($type['count']); ?></td>
                                    <td>
                                        <?php 
                                        $percentage = $stats['total_papers'] > 0 ? round(($type['count'] / $stats['total_papers']) * 100, 1) : 0;
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%%">
                                                <?php echo $percentage; ?>%%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Summary Types -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0 fw-bold">Summaries by Type</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Summary Type</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary_types as $type): ?>
                                <tr>
                                    <td><span class="badge bg-success"><?php echo ucfirst($type['summary_type']); ?></span></td>
                                    <td><?php echo number_format($type['count']); ?></td>
                                    <td>
                                        <?php 
                                        $percentage = $stats['total_summaries'] > 0 ? round(($type['count'] / $stats['total_summaries']) * 100, 1) : 0;
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%%">
                                                <?php echo $percentage; ?>%%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Activity Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0 fw-bold">Activity (Last 14 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="activityChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Top Users by Papers -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0 fw-bold">Top Users by Papers Uploaded</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Papers</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_users_papers as $index => $user): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </td>
                                    <td><span class="badge bg-primary"><?php echo number_format($user['paper_count']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Users by Summaries -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0 fw-bold">Top Users by Summaries Generated</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Summaries</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_users_summaries as $index => $user): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </td>
                                    <td><span class="badge bg-success"><?php echo number_format($user['summary_count']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Prepare data for last 14 days
const last14Days = [];
const today = new Date();
for (let i = 13; i >= 0; i--) {
    const date = new Date(today);
    date.setDate(date.getDate() - i);
    last14Days.push(date.toISOString().split('T')[0]);
}

// Prepare activity data
const paperActivity = <?php echo json_encode($daily_activity); ?>;
const summaryActivity = <?php echo json_encode($daily_summaries); ?>;

const paperData = last14Days.map(date => paperActivity[date] || 0);
const summaryData = last14Days.map(date => summaryActivity[date] || 0);

// Create chart
const ctx = document.getElementById('activityChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: last14Days.map(date => {
            const d = new Date(date);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }),
        datasets: [
            {
                label: 'Papers Uploaded',
                data: paperData,
                borderColor: 'rgb(12, 110, 253)',
                backgroundColor: 'rgba(12, 110, 253, 0.1)',
                tension: 0.5
            },
            {
                label: 'Summaries Generated',
                data: summaryData,
                borderColor: 'rgb(25, 135, 84)',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
