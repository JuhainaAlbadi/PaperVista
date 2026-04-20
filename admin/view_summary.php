<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = "View Summary";

// Get summary ID
$summary_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch summary with paper and user details
$stmt = $conn->prepare("
    SELECT s.*, p.title as paper_title, p.authors, p.file_path,
           u.first_name, u.last_name, u.email
    FROM summaries s 
    JOIN papers p ON s.paper_id = p.id 
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $summary_id);
$stmt->execute();
$result = $stmt->get_result();
$summary = $result->fetch_assoc();
$stmt->close();

if (!$summary) {
    header("Location: papers.php");
    exit();
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold">Summary Details</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Admin</a></li>
                    <li class="breadcrumb-item"><a href="papers.php">Papers</a></li>
                    <li class="breadcrumb-item active">View Summary</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="view_paper.php?id=<?php echo $summary['paper_id']; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Paper
            </a>
        </div>
    </div>
    
    <!-- Paper Info Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h3 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($summary['paper_title']); ?></h3>
            
            <div class="row">
                <div class="col-md-6">
                    <?php if ($summary['authors']): ?>
                        <p class="text-muted mb-2">
                            <i class="fas fa-user-edit me-2"></i>
                            <strong>Authors:</strong> <?php echo htmlspecialchars($summary['authors']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <p class="text-muted mb-2">
                        <i class="fas fa-user me-2"></i>
                        <strong>Summary Created By:</strong> 
                        <?php echo htmlspecialchars($summary['first_name'] . ' ' . $summary['last_name']); ?>
                        (<?php echo htmlspecialchars($summary['email']); ?>)
                    </p>
                </div>
                
                <div class="col-md-6">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-primary">
                            <?php echo ucfirst($summary['summary_type']); ?> Summary
                        </span>
                        <span class="badge bg-secondary">
                            <?php echo number_format($summary['word_count']); ?> words
                        </span>
                        <span class="badge bg-info">
                            Created: <?php echo date('M d, Y', strtotime($summary['created_at'])); ?>
                        </span>
                    </div>
                    
                    <div class="btn-group" role="group">
                        <a href="../export.php?id=<?php echo $summary['id']; ?>&format=pdf" class="btn btn-sm btn-primary">
                            <i class="fas fa-file-pdf me-1"></i>Export PDF
                        </a>
                        <a href="../export.php?id=<?php echo $summary['id']; ?>&format=docx" class="btn btn-sm btn-primary">
                            <i class="fas fa-file-word me-1"></i>Export DOCX
                        </a>
                        <a href="../export.php?id=<?php echo $summary['id']; ?>&format=txt" class="btn btn-sm btn-primary">
                            <i class="fas fa-file-alt me-1"></i>Export TXT
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Content -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fas fa-file-alt me-2"></i>Summary Content
            </h5>
        </div>
        <div class="card-body">
            <div class="summary-content" style="line-height: 1.8; font-size: 1.05rem;">
                <?php echo isset($summary['content']) && $summary['content'] ? nl2br(htmlspecialchars($summary['content'])) : '<p class="text-muted">No summary content available.</p>'; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
