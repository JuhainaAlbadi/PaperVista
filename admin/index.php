<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = "Admin Dashboard";

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total papers
$result = $conn->query("SELECT COUNT(*) as count FROM papers");
$stats['total_papers'] = $result->fetch_assoc()['count'];

// Total summaries
$result = $conn->query("SELECT COUNT(*) as count FROM summaries");
$stats['total_summaries'] = $result->fetch_assoc()['count'];

// Papers today
$result = $conn->query("SELECT COUNT(*) as count FROM papers WHERE DATE(created_at) = CURDATE()");
$stats['papers_today'] = $result->fetch_assoc()['count'];

// Summaries today
$result = $conn->query("SELECT COUNT(*) as count FROM summaries WHERE DATE(created_at) = CURDATE()");
$stats['summaries_today'] = $result->fetch_assoc()['count'];

// Recent papers
$recent_papers = [];
$result = $conn->query("
    SELECT p.*, u.email, u.first_name, u.last_name 
    FROM papers p 
    LEFT JOIN users u ON p.uploaded_by = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $recent_papers[] = $row;
}

// Recent users
$recent_users = [];
$result = $conn->query("
    SELECT id, email, first_name, last_name, role, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $recent_users[] = $row;
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold">Admin Dashboard</h2>
            <p class="text-muted">Manage papers, users, and system analytics</p>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Users</h6>
                            <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_users']); ?></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Papers</h6>
                            <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_papers']); ?></h2>
                            <small class="text-success">+<?php echo $stats['papers_today']; ?> today</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-file-alt fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Summaries</h6>
                            <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_summaries']); ?></h2>
                            <small class="text-success">+<?php echo $stats['summaries_today']; ?> today</small>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="fas fa-magic fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">Quick Actions</h5>
                    <div class="btn-group" role="group">
                        <a href="papers.php" class="btn btn-outline-primary">
                            <i class="fas fa-file-alt me-2"></i>Manage Papers
                        </a>
                        <a href="users.php" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a href="analytics.php" class="btn btn-outline-primary">
                            <i class="fas fa-chart-bar me-2"></i>View Analytics
                        </a>
                        <a href="../upload.php" class="btn btn-outline-success">
                            <i class="fas fa-upload me-2"></i>Upload Paper
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Recent Papers -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-bold mb-0">Recent Papers</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Uploaded By</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_papers as $paper): ?>
                                <tr>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($paper['title']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($paper['first_name'] . ' ' . $paper['last_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($paper['created_at'])); ?></td>
                                    <td>
                                        <a href="view_paper.php?id=<?php echo $paper['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <a href="papers.php" class="btn btn-sm btn-primary">View All Papers</a>
                </div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-bold mb-0">Recent Users</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <a href="users.php" class="btn btn-sm btn-primary">View All Users</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
