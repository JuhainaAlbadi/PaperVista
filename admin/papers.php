<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/upload.php';

requireAdmin();

$page_title = "Manage Papers";
$success = '';
$error = '';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



// Handle delete POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paper_id'])) {
    $paper_id = intval($_POST['paper_id']);
    $result = deletePaperAdmin($paper_id); 

    if ($result['success']) {
        $success = "Paper deleted successfully.";
    } else {
        $error = $result['error'] ?? "Failed to delete paper.";
    }
}


// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Build query
$query = "
    SELECT p.*, u.email, u.first_name, u.last_name,
    (SELECT COUNT(*) FROM summaries WHERE paper_id = p.id) as summary_count
    FROM papers p 
    LEFT JOIN users u ON p.uploaded_by = u.id 
    WHERE 1=1
";

$params = [];
$types = '';

if ($search) {
    $query .= " AND (p.title LIKE ? OR p.authors LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($status) {
    $query .= " AND p.processing_status = ?";
    $params[] = $status;
    $types .= 's';
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$papers = [];
while ($row = $result->fetch_assoc()) {
    $papers[] = $row;
}
$stmt->close();

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold">Manage Papers</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Admin</a></li>
                    <li class="breadcrumb-item active">Papers</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="../upload.php" class="btn btn-primary">
                <i class="fas fa-upload me-2"></i>Upload New Paper
            </a>
        </div>
    </div>
    
    <?php if ($success): ?>
        <?php echo alert($success, 'success'); ?>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <?php echo alert($error, 'error'); ?>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search by title or authors..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                    <a href="papers.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Papers Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th style="min-width: 250px;">Title</th>
                            <th>Uploaded By</th>
                            <th>File Type</th>
                            <th>Size</th>
                            <th>Summaries</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($papers)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                No papers found.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($papers as $paper): ?>
                        <tr>
                            <td><?php echo $paper['id']; ?></td>
                            <td>
                                <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($paper['title']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($paper['first_name'] . ' ' . $paper['last_name']); ?></td>
                            <td><?php echo strtoupper(pathinfo($paper['file_path'], PATHINFO_EXTENSION)); ?></td>
                            <td><?php echo formatFileSize($paper['file_size']); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo $paper['summary_count']; ?></span>
                            </td>
                            <td>
                                <?php
                                $status_class = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'completed' => 'success',
                                    'failed' => 'danger'
                                ][$paper['processing_status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo ucfirst($paper['processing_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($paper['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_paper.php?id=<?php echo $paper['id']; ?>" class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $paper['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this paper? This action cannot be undone and will also delete all associated summaries.
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm" action="papers.php">
                    <input type="hidden" name="paper_id" id="deletePaperId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_paper" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(paperId) {
    document.getElementById('deletePaperId').value = paperId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Optional: debug form submission
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    console.log('Submitting delete:', this.paper_id.value);
});

</script>

<?php include '../includes/footer.php'; ?>
