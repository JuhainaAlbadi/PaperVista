<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/openai.php';
requireLogin();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Generate Summary";
$error = '';
$success = '';
$paper = null;
$latest_summary = null;
$user_id = $_SESSION['user_id'];
$paper_id = isset($_GET['paper_id']) ? (int)$_GET['paper_id'] : 0;
$hide_generate_form = false;

// === FETCH PAPER ===
if ($paper_id > 0) {
    $stmt = $conn->prepare("
        SELECT p.*, u.first_name, u.last_name
        FROM papers p
        JOIN users u ON p.uploaded_by = u.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $paper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $paper = $result->fetch_assoc();
    $stmt->close();

    if (!$paper) {
        $error = "Paper not found.";
    } else {
        // Log view
        $view_stmt = $conn->prepare("INSERT IGNORE INTO paper_views (paper_id, user_id) VALUES (?, ?)");
        $view_stmt->bind_param("ii", $paper_id, $user_id);
        $view_stmt->execute();
        $view_stmt->close();
    }
}

// === UPDATE METADATA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_metadata']) && $paper && $paper['uploaded_by'] == $user_id) {
    $new_title = trim($_POST['title']);
    $new_authors = trim($_POST['authors'] ?? '');
    $new_abstract = trim($_POST['abstract'] ?? '');
    $new_year = trim($_POST['publication_year'] ?? '');
    $new_year = (preg_match('/^\d{4}$/', $new_year) && $new_year >= 1900 && $new_year <= date('Y')) ? $new_year : null;

    if (empty($new_title)) {
        $error = "Title is required.";
    } else {
        $stmt = $conn->prepare("UPDATE papers SET title = ?, authors = ?, abstract = ?, publication_year = ? WHERE id = ? AND uploaded_by = ?");
        $stmt->bind_param("ssssii", $new_title, $new_authors, $new_abstract, $new_year, $paper_id, $user_id);
        if ($stmt->execute()) {
            $success = "Paper details updated successfully!";
            // Refresh paper data
            $stmt = $conn->prepare("SELECT p.*, u.first_name, u.last_name FROM papers p JOIN users u ON p.uploaded_by = u.id WHERE p.id = ?");
            $stmt->bind_param("i", $paper_id);
            $stmt->execute();
            $paper = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Failed to update paper details.";
        }
        $stmt->close();
    }
}

// === GENERATE SUMMARY — STRICT 2 TOTAL LIMIT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_summary']) && $paper) {
    $summary_type = $_POST['summary_type'] ?? 'medium';

    if (!in_array($summary_type, ['short', 'medium', 'detailed'])) {
        $error = "Invalid summary type.";
    } else {
        // Count how many summaries this user already has
        if (!isAdmin($user_id)) {
            $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM summaries WHERE user_id = ?");
            $count_stmt->bind_param("i", $user_id);
            $count_stmt->execute();
            $total_used = $count_stmt->get_result()->fetch_assoc()['total'];
            $count_stmt->close();

            if ($total_used >= 2) {
                $error = "Free trial limit reached! You can only generate 2 summaries total. Contact admin for more.";
                $hide_generate_form = true;
            }
        }

        if (empty($error)) {
            if (empty($paper['text_content'])) {
                $error = "No text found in this paper. Please re-upload the PDF.";
            } else {
                $ai_result = generateSummary($paper['text_content'], $summary_type, $paper['title']);

                if ($ai_result['success']) {
                    $stmt = $conn->prepare("INSERT INTO summaries (paper_id, user_id, summary_type, content, word_count, processing_time, ai_model_used, created_at) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iissdss", 
                        $paper_id, 
                        $user_id, 
                        $summary_type, 
                        $ai_result['content'], 
                        $ai_result['word_count'], 
                        $ai_result['processing_time'], 
                        $ai_result['model']
                    );

                    if ($stmt->execute()) {
                        $success = "Summary generated successfully!";
                        header("Location: summarize.php?paper_id=$paper_id&success=1");
                        exit();
                    } else {
                        $error = "Failed to save summary.";
                    }
                    $stmt->close();
                } else {
                    $error = "AI Error: " . ($ai_result['error'] ?? 'Try again');
                }
            }
        }
    }
}

// === FETCH LATEST SUMMARY (so it shows after redirect) ===
if ($paper) {
    $stmt = $conn->prepare("SELECT s.*, DATE_FORMAT(s.created_at, '%M %d, %Y at %l:%i %p') as formatted_date 
                            FROM summaries s 
                            WHERE s.paper_id = ? AND s.user_id = ? 
                            ORDER BY s.created_at DESC LIMIT 1");
    $stmt->bind_param("ii", $paper_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $latest_summary = $result->fetch_assoc();
    $stmt->close();
}

// Count total summaries for banner
$total_used = 0;
if (!isAdmin($user_id)) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM summaries WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_used = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}
$remaining = max(0, 2 - $total_used);

include 'includes/header.php';
?>

<div class="container py-5">
    <?php if (!$paper): ?>
        <div class="alert alert-warning">
            <h4>No Paper Selected</h4>
            <p>Upload a paper first to generate summaries.</p>
            <a href="upload.php" class="btn btn-primary">Upload Paper</a>
        </div>
    <?php else: ?>

        <!-- FREE TRIAL BANNER — YOUR ORIGINAL STYLE -->
        <?php if (!isAdmin()): ?>
            <div class="alert alert-info border-0 rounded-3 shadow-sm mb-4 text-center py-4">
                <i class="fas fa-gift fs-3 me-2"></i>
                <strong>Free Trial:</strong> You have
                <span class="fs-4 fw-bold text-primary"><?php echo $remaining; ?></span>
                summary(ies) remaining <strong>across all your papers</strong>.
                <?php if ($remaining <= 0): ?>
                    <br><small class="text-muted">Contact admin for unlimited access</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Paper Info — 100% SAME -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h3 class="card-title fw-bold mb-0">
                                <?php echo htmlspecialchars($paper['title'] ?: 'Untitled Paper'); ?>
                            </h3>
                            <?php if ($paper['uploaded_by'] == $user_id): ?>
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editMetadataModal">
                                    Edit
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($paper['authors']): ?>
                        <p class="text-muted mb-2"><strong>Authors:</strong> <?php echo htmlspecialchars($paper['authors']); ?></p>
                        <?php endif; ?>
                        <?php if ($paper['abstract']): ?>
                        <p class="text-muted small mb-2"><strong>Abstract:</strong> <?php echo nl2br(htmlspecialchars(substr($paper['abstract'], 0, 300))) . '...'; ?></p>
                        <?php endif; ?>
                        <div class="row text-muted small">
                            <div class="col-md-6">File Type: <?php echo strtoupper(pathinfo($paper['file_path'], PATHINFO_EXTENSION)); ?></div>
                            <div class="col-md-6">Size: <?php echo formatFileSize($paper['file_size']); ?></div>
                        </div>
                        <div class="row text-muted small mt-2">
                            <div class="col-md-6">Uploaded: <?php echo date('M d, Y', strtotime($paper['created_at'])); ?></div>
                            <div class="col-md-6">Words: <?php echo number_format(str_word_count($paper['text_content'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal — UNCHANGED -->
                <?php if ($paper['uploaded_by'] == $user_id): ?>
                <div class="modal fade" id="editMetadataModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <form method="POST">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Paper Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="update_metadata" value="1">
                                    <div class="mb-3">
                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($paper['title']); ?>" required>
                                    </div>
                                    <div class="mb-3"><label class="form-label">Authors</label><input type="text" name="authors" class="form-control" value="<?php echo htmlspecialchars($paper['authors']); ?>"></div>
                                    <div class="mb-3"><label class="form-label">Abstract</label><textarea name="abstract" class="form-control" rows="4"><?php echo htmlspecialchars($paper['abstract']); ?></textarea></div>
                                    <div class="mb-3"><label class="form-label">Year</label><input type="text" name="publication_year" class="form-control" value="<?php echo htmlspecialchars($paper['publication_year']); ?>" placeholder="2024"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- SHOW GENERATED SUMMARY — WORKS NOW -->
                <?php if ($latest_summary): ?>
                <div class="card shadow-sm mb-4 border-success">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 text-success">
                            <?php echo ucfirst($latest_summary['summary_type']); ?> Summary Generated
                        </h4>
                        <span class="badge bg-success"><?php echo $latest_summary['processing_time']; ?>s</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="summary-content mb-4" style="line-height: 1.8;">
                            <?php echo nl2br(htmlspecialchars($latest_summary['content'])); ?>
                        </div>
                        <div class="row text-muted small mb-3">
                            <div class="col-md-6">Word Count: <?php echo $latest_summary['word_count']; ?></div>
                            <div class="col-md-6">Model: <?php echo $latest_summary['ai_model_used']; ?></div>
                        </div>
                        <hr>
                        <div class="row g-2">
                            <div class="col-md-4"><a href="export.php?summary_id=<?php echo $latest_summary['id']; ?>&format=pdf" class="btn btn-outline-primary w-100">Export PDF</a></div>
                            <div class="col-md-4"><a href="export.php?summary_id=<?php echo $latest_summary['id']; ?>&format=docx" class="btn btn-outline-primary w-100">Export DOCX</a></div>
                            <div class="col-md-4"><a href="export.php?summary_id=<?php echo $latest_summary['id']; ?>&format=txt" class="btn btn-outline-primary w-100">Export TXT</a></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- GENERATE FORM — ONLY IF ALLOWED -->
                <?php if (!$hide_generate_form && ($remaining > 0 || isAdmin($user_id))): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Generate AI Summary</h4>
                        <form method="POST" id="summaryForm">
                            <input type="hidden" name="generate_summary" value="1">
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Summary Type</label>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="summary_type" id="short" value="short">
                                    <label class="form-check-label" for="short"><strong>Short</strong> (2-3 paragraphs)</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="summary_type" id="medium" value="medium" checked>
                                    <label class="form-check-label" for="medium"><strong>Medium</strong> (5-7 paragraphs)</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="summary_type" id="detailed" value="detailed">
                                    <label class="form-check-label" for="detailed"><strong>Detailed</strong> (Comprehensive)</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100" id="generateBtn">
                                Generate Summary
                            </button>
                        </form>
                    </div>
                </div>
                <?php elseif ($remaining == 0 && !isAdmin($user_id)): ?>
                <div class="alert alert-warning text-center py-5 mb-4">
                    <i class="fas fa-lock fa-3x mb-4"></i><br>
                    <strong>You have reached the free limit of 2 summaries.</strong><br><br>
                    Contact admin for unlimited access.
                </div>
                <?php endif; ?>
            </div>

            <!-- Previous Summaries — YOUR ORIGINAL -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Previous Summaries</h5>
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM summaries WHERE paper_id = ? AND user_id = ? ORDER BY created_at DESC");
                        $stmt->bind_param("ii", $paper_id, $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        ?>
                        <?php if ($result->num_rows == 0): ?>
                            <p class="text-muted">No summaries yet.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php while ($sum = $result->fetch_assoc()): ?>
                                    <a href="summarize.php?paper_id=<?php echo $paper_id; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo ucfirst($sum['summary_type']); ?> Summary</h6>
                                            <small><?php echo date('M d', strtotime($sum['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1 small text-muted">
                                            <?php echo $sum['word_count']; ?> words • <?php echo $sum['processing_time']; ?>s
                                        </p>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                        <?php $stmt->close(); ?>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Actions</h5>
                        <div class="d-grid gap-2">
                            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                            <a href="upload.php" class="btn btn-outline-primary">Upload New Paper</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('summaryForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('generateBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating... (20-30s)';
});
</script>

<?php include 'includes/footer.php'; ?>