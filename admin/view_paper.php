<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = "View Paper";

// Get paper ID
$paper_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch paper with user details
$stmt = $conn->prepare("
    SELECT p.*, u.email, u.first_name, u.last_name 
    FROM papers p 
    LEFT JOIN users u ON p.uploaded_by = u.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $paper_id);
$stmt->execute();
$result = $stmt->get_result();
$paper = $result->fetch_assoc();
$stmt->close();

if (!$paper) {
    header("Location: papers.php");
    exit();
}

// Fetch all summaries for this paper
$summaries = [];
$stmt = $conn->prepare("
    SELECT s.*, u.first_name, u.last_name, u.email
    FROM summaries s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.paper_id = ?
    ORDER BY s.created_at DESC
");
$stmt->bind_param("i", $paper_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $summaries[] = $row;
}
$stmt->close();

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold">Paper Details</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Admin</a></li>
                    <li class="breadcrumb-item"><a href="papers.php">Papers</a></li>
                    <li class="breadcrumb-item active">View Paper</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="papers.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Papers
            </a>
        </div>
    </div>
    
    <!-- Paper Info Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h3 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($paper['title']); ?></h3>
                    
                    <?php if ($paper['authors']): ?>
                        <p class="text-muted mb-3">
                            <i class="fas fa-user-edit me-2"></i>
                            <strong>Authors:</strong> <?php echo htmlspecialchars($paper['authors']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (isset($paper['journal_name']) && $paper['journal_name']): ?>
                        <p class="text-muted mb-3">
                            <i class="fas fa-book me-2"></i>
                            <strong>Journal:</strong> <?php echo htmlspecialchars($paper['journal_name']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (isset($paper['publication_year']) && $paper['publication_year']): ?>
                        <p class="text-muted mb-3">
                            <i class="fas fa-calendar me-2"></i>
                            <strong>Publication Year:</strong> <?php echo htmlspecialchars($paper['publication_year']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (isset($paper['doi']) && $paper['doi']): ?>
                        <p class="text-muted mb-3">
                            <i class="fas fa-link me-2"></i>
                            <strong>DOI:</strong> <?php echo htmlspecialchars($paper['doi']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($paper['abstract']): ?>
                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="fas fa-align-left me-2"></i>Abstract</h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($paper['abstract'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="bg-light p-3 rounded">
                        <h6 class="fw-bold mb-3">Paper Information</h6>
                        
                        <div class="mb-2">
                            <small class="text-muted">Uploaded By:</small><br>
                            <strong><?php echo htmlspecialchars($paper['first_name'] . ' ' . $paper['last_name']); ?></strong>
                            <br><small><?php echo htmlspecialchars($paper['email']); ?></small>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-2">
                            <small class="text-muted">File Type:</small><br>
                            <strong><?php echo strtoupper(pathinfo($paper['file_path'], PATHINFO_EXTENSION)); ?></strong>
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">File Size:</small><br>
                            <strong><?php echo formatFileSize($paper['file_size']); ?></strong>
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">Status:</small><br>
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
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">Uploaded:</small><br>
                            <strong><?php echo date('M d, Y g:i A', strtotime($paper['created_at'])); ?></strong>
                        </div>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <a href="../<?php echo htmlspecialchars($paper['file_path']); ?>" class="btn btn-primary btn-sm" download>
                                <i class="fas fa-download me-2"></i>Download File
                            </a>
                            <a href="../summarize.php?paper_id=<?php echo $paper['id']; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-magic me-2"></i>Generate Summary
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summaries Section -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fas fa-list me-2"></i>Generated Summaries (<?php echo count($summaries); ?>)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($summaries)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No summaries generated yet</h6>
                    <p class="text-muted small mb-3">Generate a summary to get started</p>
                    <a href="../summarize.php?paper_id=<?php echo $paper['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-magic me-2"></i>Generate Summary
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Created By</th>
                                <th>Word Count</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summaries as $summary): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo ucfirst($summary['summary_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($summary['first_name'] . ' ' . $summary['last_name']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($summary['email']); ?></small>
                                </td>
                                <td><?php echo number_format($summary['word_count']); ?> words</td>
                                <td><?php echo date('M d, Y g:i A', strtotime($summary['created_at'])); ?></td>
                                <td>
                                    <a href="view_summary.php?id=<?php echo $summary['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
