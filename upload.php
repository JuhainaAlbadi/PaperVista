<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/upload.php';

requireLogin();

$page_title = "Upload Paper";
$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['paper_file'])) {
    $result = handleFileUpload($_FILES['paper_file'], $user_id);
    
    if ($result['success']) {
        $success = "Paper uploaded successfully! Redirecting...";
        $paper_id = $result['paper_id'];
        echo "<script>
            setTimeout(() => {
                window.location.href = 'summarize.php?paper_id=$paper_id';
            }, 1500);
        </script>";
    } else {
        $error = $result['error'];
    }
}

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

include 'includes/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($success): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: '<?php echo addslashes($success); ?>',
        timer: 1500,
        showConfirmButton: false
    });
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Upload Failed',
        text: '<?php echo addslashes($error); ?>'
    });
</script>
<?php endif; ?>

<section class="py-5 bg-light min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <!-- Upload Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-5">
                            <h2 class="fw-bold text-dark">Upload Research Paper</h2>
                            <p class="text-muted">PDF, DOCX, TXT • Max 100MB</p>
                        </div>

                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <!-- File Input -->
                            <div class="mb-4">
                                <label for="paper_file" class="form-label fw-semibold">Select File</label>
                                <input type="file" name="paper_file" id="paper_file" class="form-control form-control-lg" 
                                       accept=".pdf,.docx,.txt" required>
                                <div class="form-text">Supported: PDF, DOCX, TXT • Max size: 100MB</div>
                            </div>

                            <!-- Title -->
                            <div class="mb-4">
                                <label for="title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control form-control-lg" 
                                       placeholder="Enter paper title" 
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                            </div>

                            <!-- Authors -->
                            <div class="mb-4">
                                <label for="authors" class="form-label fw-semibold">Authors</label>
                                <input type="text" name="authors" id="authors" class="form-control form-control-lg" 
                                       placeholder="e.g. John Doe, Jane Smith" 
                                       value="<?php echo isset($_POST['authors']) ? htmlspecialchars($_POST['authors']) : ''; ?>">
                                <div class="form-text">Optional • Separate with commas</div>
                            </div>

                            <!-- Submit -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Upload Paper
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recent Uploads -->
                
<?php if (!empty($recent_papers)): ?>
<div class="mt-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="fw-semibold text-primary mb-0 me-2">Recently Uploaded</h5>
        <span class="badge bg-light text-primary border border-primary small">
            <?php echo count($recent_papers); ?> latest
        </span>
    </div>

    <div class="border rounded-3 overflow-hidden shadow-sm">
        <?php foreach ($recent_papers as $index => $paper): ?>
        <a href="summarize.php?paper_id=<?php echo $paper['id']; ?>" 
           class="text-decoration-none text-dark">
            <div class="p-3 <?php echo $index < count($recent_papers) - 1 ? 'border-bottom' : ''; ?> 
                         hover-bg-light transition-all">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-fill me-3">
                        <div class="fw-medium text-truncate" style="max-width: 400px;">
                            <?php echo htmlspecialchars($paper['title'] ?: 'Untitled Paper'); ?>
                        </div>
                        <?php if (!empty($paper['authors'])): ?>
                            <div class="text-muted small mt-1">
                                by <?php echo htmlspecialchars($paper['authors']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="text-muted small mt-1">
                            <?php echo date('M j, Y \a\t g:i A', strtotime($paper['created_at'])); ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-secondary rounded-pill px-3 py-2">
                            <?php echo strtoupper(pathinfo($paper['file_path'], PATHINFO_EXTENSION)); ?>
                        </span>
                    </div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- View All Link -->
    <div class="text-center mt-3">
        <a href="search.php" class="text-decoration-none text-primary fw-medium small">
            View all uploaded papers
        </a>
    </div>
</div>
<?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>