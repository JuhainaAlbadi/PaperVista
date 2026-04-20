<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

requireLogin();

$page_title = "View Summary";

// Get summary ID
$summary_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch summary with paper details
// Admins can view all summaries, regular users can only view their own
if (isAdmin()) {
    $stmt = $conn->prepare("
        SELECT s.*, p.title as paper_title, p.authors, p.file_path
        FROM summaries s 
        JOIN papers p ON s.paper_id = p.id 
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $summary_id);
} else {
    $stmt = $conn->prepare("
        SELECT s.*, p.title as paper_title, p.authors, p.file_path
        FROM summaries s 
        JOIN papers p ON s.paper_id = p.id 
        WHERE s.id = ? AND s.user_id = ?
    ");
    $stmt->bind_param("ii", $summary_id, $_SESSION['user_id']);
}
$stmt->execute();
$result = $stmt->get_result();
$summary = $result->fetch_assoc();
$stmt->close();

if (!$summary) {
    header("Location: dashboard.php");
    exit();
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Paper Info Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($summary['paper_title']); ?></h2>
                    
                    <?php if ($summary['authors']): ?>
                        <p class="text-muted mb-3">
                            <i class="fas fa-user-edit me-2"></i><?php echo htmlspecialchars($summary['authors']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-primary">
                            <?php echo ucfirst($summary['summary_type']); ?> Summary
                        </span>
                        <span class="badge bg-secondary">
                            <?php echo $summary['word_count']; ?> words
                        </span>
                        <span class="badge bg-info text-dark">
                            <?php echo $summary['ai_model_used']; ?>
                        </span>
                        <span class="badge bg-success">
                            <?php echo $summary['processing_time']; ?>s processing
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Summary Content Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body p-5">
                    <div class="summary-content" style="line-height: 1.8; text-align: justify; font-size: 1.05rem;">
                        <?php echo nl2br(htmlspecialchars($summary['content'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Actions Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">Export Summary</h5>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <a href="export.php?summary_id=<?php echo $summary['id']; ?>&format=pdf" class="btn btn-outline-primary w-100">
                                <i class="fas fa-file-pdf me-2"></i>Export as PDF
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="export.php?summary_id=<?php echo $summary['id']; ?>&format=docx" class="btn btn-outline-primary w-100">
                                <i class="fas fa-file-word me-2"></i>Export as DOCX
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="export.php?summary_id=<?php echo $summary['id']; ?>&format=txt" class="btn btn-outline-primary w-100">
                                <i class="fas fa-file-alt me-2"></i>Export as TXT
                            </a>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex gap-2">
                        <a href="summarize.php?paper_id=<?php echo $summary['paper_id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-redo me-2"></i>Generate Another Summary
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Metadata Card -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title fw-bold mb-3">Summary Details</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Generated:</strong></td>
                            <td><?php echo date('F d, Y \a\t h:i A', strtotime($summary['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Summary Type:</strong></td>
                            <td><?php echo ucfirst($summary['summary_type']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Word Count:</strong></td>
                            <td><?php echo number_format($summary['word_count']); ?> words</td>
                        </tr>
                        <tr>
                            <td><strong>AI Model:</strong></td>
                            <td><?php echo $summary['ai_model_used']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Processing Time:</strong></td>
                            <td><?php echo $summary['processing_time']; ?> seconds</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
