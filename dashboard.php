<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Dashboard";

// Redirect if not logged in
requireLogin();

// Get user statistics
$user_id = $_SESSION['user_id'];


// === DELETE PAPER LOGIC (WITH SWEET ALERT) ===
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $paper_id = intval($_GET['delete']);

    $stmt = $conn->prepare("SELECT file_path, title FROM papers WHERE id = ? AND uploaded_by = ?");
    $stmt->bind_param("ii", $paper_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $paper = $result->fetch_assoc();
        if (file_exists($paper['file_path'])) {
            unlink($paper['file_path']);
        }
        $delete_stmt = $conn->prepare("DELETE FROM papers WHERE id = ? AND uploaded_by = ?");
        $delete_stmt->bind_param("ii", $paper_id, $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        $success = "Paper '{$paper['title']}' deleted successfully.";
    } else {
        $error = "You can only delete your own papers.";
    }
    $stmt->close();
}

// Count papers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM papers WHERE uploaded_by = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$paper_count = $result->fetch_assoc()['count'];
$stmt->close();

// Count summaries
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM summaries WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$summary_count = $result->fetch_assoc()['count'];
$stmt->close();

// Calculate time saved
$time_saved = $summary_count * 2;

// Get recent papers
$recent_papers = [];
$stmt = $conn->prepare("SELECT * FROM papers WHERE uploaded_by = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_papers[] = $row;
}
$stmt->close();

// Get recent summaries
$recent_summaries = [];
$stmt = $conn->prepare("
    SELECT s.*, p.title as paper_title 
    FROM summaries s 
    JOIN papers p ON s.paper_id = p.id 
    WHERE s.user_id = ? 
    ORDER BY s.created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_summaries[] = $row;
}
$stmt->close();

include 'includes/header.php';

?>

<!-- SWEET ALERT CDN (ADD TO HEADER OR HERE) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- SUCCESS / ERROR ALERTS -->
<?php if (isset($success)): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Deleted!',
        text: '<?php echo addslashes($success); ?>',
        timer: 3000,
        showConfirmButton: false
    });
</script>
<?php endif; ?>

<?php if (isset($error)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo addslashes($error); ?>',
        timer: 3000,
        showConfirmButton: false
    });
</script>
<?php endif; ?>

<!-- Dashboard Section -->
<section class="py-5 bg-light">
    <div class="container">
        <!-- Welcome Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h1 class="mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h1>
                                <p class="text-muted mb-0">
                                    Ready to accelerate your research? Upload a paper or search through our collection.
                                </p>
                            </div>
                            <div class="mt-3 mt-md-0">
                                <a href="upload.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-upload me-2"></i>Upload Paper
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                        <h3 class="h2 mb-2"><?php echo $paper_count; ?></h3>
                        <p class="text-muted mb-0">Papers Uploaded</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-brain fa-3x text-success mb-3"></i>
                        <h3 class="h2 mb-2"><?php echo $summary_count; ?></h3>
                        <p class="text-muted mb-0">AI Summaries</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-clock fa-3x text-info mb-3"></i>
                        <h3 class="h2 mb-2"><?php echo $time_saved; ?>h</h3>
                        <p class="text-muted mb-0">Time Saved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-star fa-3x text-warning mb-3"></i>
                        <h3 class="h2 mb-2">5.0</h3>
                        <p class="text-muted mb-0">Avg. Rating</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Activity -->
        <div class="row">
            <!-- Quick Actions -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-bolt text-primary me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="upload.php" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Upload Research Paper
                            </a>
                            <a href="search.php" class="btn btn-outline-primary">
                                <i class="fas fa-search me-2"></i>Search Papers
                            </a>
                            <!-- FIXED: My Papers â†’ shows only user's papers -->
                            <a href="search.php?uploaded_by=<?php echo $_SESSION['user_id']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-file-alt me-2"></i>My Papers
                            </a>
                            <a href="profile.php" class="btn btn-outline-info">
                                <i class="fas fa-user-edit me-2"></i>Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-history text-primary me-2"></i>Recent Summaries
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_summaries)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No summaries yet</h6>
                                <p class="text-muted small mb-3">Upload your first research paper to get started!</p>
                                <a href="upload.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-upload me-2"></i>Upload Paper
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_summaries as $summary): ?>
                                    <a href="view_summary.php?id=<?php echo $summary['id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo ucfirst($summary['summary_type']); ?> Summary</h6>
                                                <p class="mb-1 small text-truncate" style="max-width: 300px;">
                                                    <?php echo htmlspecialchars($summary['paper_title']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($summary['created_at'])); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-primary"><?php echo $summary['word_count']; ?> words</span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Papers -->
        <?php if (!empty($recent_papers)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-file-alt text-primary me-2"></i>Recent Papers
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>File Type</th>
                                        <th>Size</th>
                                        <th>Uploaded</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_papers as $paper): ?>
                                    <tr>
                                        <td>
                                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <strong><?php echo htmlspecialchars($paper['title']); ?></strong>
                                                <?php if(!empty($paper['authors'])): ?>
                                                    <br><small>by <?php echo htmlspecialchars($paper['authors']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo strtoupper(pathinfo($paper['file_path'], PATHINFO_EXTENSION)); ?></td>
                                        <td><?php echo formatFileSize($paper['file_size']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($paper['created_at'])); ?></td>
                                        <td>
                                            <!-- GENERATE SUMMARY -->
                                            <a href="summarize.php?paper_id=<?php echo $paper['id']; ?>" class="btn btn-sm btn-primary">
                                                Generate Summary
                                            </a>

                                            <!-- DELETE BUTTON WITH SWEET ALERT -->
                                            <button type="button" class="btn btn-sm btn-danger ms-1" 
                                                    onclick="confirmDelete(<?php echo $paper['id']; ?>, '<?php echo addslashes(htmlspecialchars($paper['title'] ?: 'Untitled')); ?>')">
                                                Delete
                                            </button>
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
        <?php endif; ?>

        <!-- Getting Started Guide -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-rocket text-primary me-2"></i>Getting Started
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <div class="mb-3">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                         style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold;">
                                        1
                                    </div>
                                </div>
                                <h6 class="fw-bold">Upload Papers</h6>
                                <p class="text-muted small">
                                    Upload your research papers in PDF, DOCX, or TXT format
                                </p>
                            </div>
                            <div class="col-md-4 text-center mb-4">
                                <div class="mb-3">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                         style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold;">
                                        2
                                    </div>
                                </div>
                                <h6 class="fw-bold">Get AI Summary</h6>
                                <p class="text-muted small">
                                    Our AI will analyze and generate a comprehensive summary
                                </p>
                            </div>
                            <div class="col-md-4 text-center mb-4">
                                <div class="mb-3">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                         style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold;">
                                        3
                                    </div>
                                </div>
                                <h6 class="fw-bold">Save & Export</h6>
                                <p class="text-muted small">
                                    Save summaries to your library and export in various formats
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SWEET ALERT DELETE CONFIRM SCRIPT -->
<script>
function confirmDelete(paperId, paperTitle) {
    Swal.fire({
        title: 'Are you sure?',
        text: `Delete "${paperTitle}"? This cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'dashboard.php?delete=' + paperId;
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?> 

