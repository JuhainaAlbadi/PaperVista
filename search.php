<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
$page_title = "Search Papers";
requireLogin();

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 6;
$offset = ($page - 1) * $per_page;
$results = [];
$result_count = 0;
$total_pages = 1;
$user_id = $_SESSION['user_id'];

// AUTO-FILTER: Show only user's own papers when ?uploaded_by= is used
$extra_where = "";
if (isset($_GET['uploaded_by']) && isLoggedIn()) {
    $requested_user = intval($_GET['uploaded_by']);
    if ($requested_user === $_SESSION['user_id']) {
        $extra_where = " AND p.uploaded_by = " . $_SESSION['user_id'];  // ← IMPORTANT: AND, not WHERE
    } else {
        $extra_where = " AND 1=0"; // security: show nothing
    }
}

// Build base URL for pagination
$base_url = '?';
if (!empty($query)) {
    $base_url .= 'q=' . urlencode($query) . '&';
}
if (!empty($_GET['uploaded_by'])) {
    $base_url .= 'uploaded_by=' . $_GET['uploaded_by'] . '&';
}

// FETCH PAPERS — NOW RESPECTS BOTH SEARCH + MY PAPERS FILTER
if (!empty($query)) {
    $search_term = "%" . $conn->real_escape_string($query) . "%";
    $sql = "SELECT p.*, u.first_name, u.last_name,
                   (SELECT COUNT(*) FROM summaries s WHERE s.paper_id = p.id) as summary_count,
                   (SELECT COUNT(*) FROM paper_views pv WHERE pv.paper_id = p.id) AS view_count
            FROM papers p
            JOIN users u ON p.uploaded_by = u.id
            WHERE (p.title LIKE ? OR p.authors LIKE ? OR p.abstract LIKE ?)
            $extra_where
            ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_results = [];
    while ($row = $result->fetch_assoc()) {
        $all_results[] = $row;
    }
    $result_count = count($all_results);
    $total_pages = ceil($result_count / $per_page);
    $results = array_slice($all_results, $offset, $per_page);
    $stmt->close();

    // Log search
    logSearch($conn, $user_id, $query, $result_count);

} else {
    // NO SEARCH QUERY — SHOW ALL (or only user's if filtered)
    $count_sql = "SELECT COUNT(*) as total FROM papers p WHERE 1=1 $extra_where";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute();
    $result_count = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();

    $total_pages = ceil($result_count / $per_page);

    $sql = "SELECT p.*, u.first_name, u.last_name,
                   (SELECT COUNT(*) FROM summaries s WHERE s.paper_id = p.id) as summary_count,
                   (SELECT COUNT(*) FROM paper_views pv WHERE pv.paper_id = p.id) AS view_count
            FROM papers p
            JOIN users u ON p.uploaded_by = u.id
            WHERE 1=1 $extra_where
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}

include 'includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container">
        <!-- Search Header -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="display-5 fw-bold mb-3">Search Research Papers</h1>
                <p class="lead text-muted mb-4">
                    Discover academic papers and get AI-powered summaries
                </p>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <!-- SMART SEARCH BAR WITH SUGGESTIONS -->
                        <div style="position:relative; max-width:600px; margin:0 auto;">
                            <form method="GET" action="search.php" class="d-flex gap-2">
                                <div class="input-group">
                                    <input type="text"
                                           name="q"
                                           id="search-input"
                                           class="form-control form-control-lg"
                                           placeholder="Search for papers, authors, topics..."
                                           value="<?php echo htmlspecialchars($query); ?>"
                                           autocomplete="off">
                                    <button type="submit" class="btn btn-primary">
                                        Search
                                    </button>
                                </div>
                            </form>

                            <!-- SUGGESTIONS DROPDOWN -->
                            <div id="suggestions" 
                                 style="display:none; position:absolute; top:100%; left:0; right:0; 
                                        background:white; border:1px solid #ddd; border-top:none; 
                                        max-height:200px; overflow-y:auto; z-index:999; 
                                        box-shadow:0 4px 6px rgba(0,0,0,0.1); font-size:14px;">
                            </div>
                        </div>

                        <script>
                        // AUTOCOMPLETE — CALLS get_suggestions.php
                        document.getElementById('search-input').addEventListener('input', function() {
                            let q = this.value.trim();
                            let box = document.getElementById('suggestions');
                            
                            if (q.length < 1) {
                                box.style.display = 'none';
                                return;
                            }

                            box.innerHTML = '<div style="padding:10px 15px; color:#666; font-size:12px;">Loading...</div>';
                            box.style.display = 'block';

                            fetch('get_suggestions.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'q=' + encodeURIComponent(q)
                            })
                            .then(r => {
                                if (!r.ok) throw new Error('HTTP ' + r.status);
                                return r.json();
                            })
                            .then(data => {
                                box.innerHTML = '';
                                if (data.length > 0) {
                                    data.forEach(term => {
                                        let div = document.createElement('div');
                                        let regex = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                                        div.innerHTML = term.replace(regex, '<strong>$1</strong>');
                                        div.style.padding = '10px 15px';
                                        div.style.cursor = 'pointer';
                                        div.style.borderBottom = '1px solid #eee';
                                        div.style.backgroundColor = '#fff';
                                        div.onmouseover = () => div.style.backgroundColor = '#f8f9fa';
                                        div.onmouseout = () => div.style.backgroundColor = '#fff';
                                        div.onclick = () => {
                                            document.getElementById('search-input').value = term;
                                            box.style.display = 'none';
                                            document.querySelector('form').submit();
                                        };
                                        box.appendChild(div);
                                    });
                                } else {
                                    box.innerHTML = '<div style="padding:10px 15px; color:#999;">No suggestions</div>';
                                }
                            })
                            .catch(err => {
                                console.error('Suggestion error:', err);
                                box.innerHTML = '<div style="padding:10px 15px; color:#c00; font-size:12px;">Error loading</div>';
                            });
                        });

                        // Hide dropdown when clicking outside
                        document.addEventListener('click', function(e) {
                            if (!e.target.closest('#search-input') && !e.target.closest('#suggestions')) {
                                document.getElementById('suggestions').style.display = 'none';
                            }
                        });
                        </script>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($query)): ?>
            <!-- Search Results -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h4 mb-0">Results for "<?php echo htmlspecialchars($query); ?>"</h3>
                        <span class="badge bg-primary fs-6">
                            <?php echo $result_count; ?> result<?php echo $result_count !== 1 ? 's' : ''; ?>
                        </span>
                    </div>

                    <?php if (empty($results)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted">No results found</h4>
                            <p class="text-muted mb-4">Try adjusting your search terms.</p>
                            <a href="search.php" class="btn btn-primary">Browse All</a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($results as $paper): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <span class="badge bg-info">
                                                    <?php echo strtoupper(pathinfo($paper['file_path'], PATHINFO_EXTENSION)); ?>
                                                </span>
                                                <span class="text-muted small">
                                                    <?php echo $paper['publication_year'] ?: date('Y', strtotime($paper['created_at'])); ?>
                                                </span>
                                            </div>

                                            <h5 class="card-title mb-3">
                                                <?php echo htmlspecialchars($paper['title'] ?: 'Untitled'); ?>
                                            </h5>

                                            <?php if ($paper['authors']): ?>
                                                <p class="card-text text-muted mb-3">
                                                    <strong>Authors:</strong> <?php echo htmlspecialchars($paper['authors']); ?>
                                                </p>
                                            <?php endif; ?>

                                            <p class="card-text mb-4">
                                                <?php echo substr(htmlspecialchars($paper['abstract'] ?? ''), 0, 200) . '...'; ?>
                                            </p>

                                            <div class="d-grid gap-2">
                                                <a href="summarize.php?paper_id=<?php echo $paper['id']; ?>" class="btn btn-primary btn-sm">
                                                    View Details
                                                </a>
                                                <span class="badge bg-secondary align-self-center">
                                                    <?php echo $paper['view_count']; ?> view<?php echo $paper['view_count'] != 1 ? 's' : ''; ?>
                                                </span>

                                                <?php if ($paper['summary_count'] > 0): ?>
                                                    <a href="view_summary.php?paper_id=<?php echo $paper['id']; ?>" class="btn btn-outline-success btn-sm">
                                                        View AI Summary
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-primary btn-sm" disabled>
                                                        No Summary Yet
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- PAGINATION -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Search results pagination" class="mt-4">
                                <ul class="divination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo $base_url; ?>page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo $base_url; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo $base_url; ?>page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>


            <!-- Browse Categories -->
            <div class="row mb-5">
                <div class="col-12">
                    <h3 class="h4 mb-4 text-center">Browse by Category</h3>
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <a href="search.php?q=artificial+intelligence" class="text-decoration-none">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-robot fa-3x text-primary mb-3"></i>
                                        <h6 class="fw-bold">Artificial Intelligence</h6>
                                        <p class="text-muted small">AI research and applications</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-4">
                            <a href="search.php?q=machine+learning" class="text-decoration-none">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-brain fa-3x text-success mb-3"></i>
                                        <h6 class="fw-bold">Machine Learning</h6>
                                        <p class="text-muted small">ML algorithms and models</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-4">
                            <a href="search.php?q=data+science" class="text-decoration-none">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                                        <h6 class="fw-bold">Data Science</h6>
                                        <p class="text-muted small">Data analysis and visualization</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-4">
                            <a href="search.php?q=computer+vision" class="text-decoration-none">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-eye fa-3x text-warning mb-3"></i>
                                        <h6 class="fw-bold">Computer Vision</h6>
                                        <p class="text-muted small">Image and video processing</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- All Public Papers -->
            <div class="row">
                <div class="col-12">
                    <h3 class="h4 mb-4">All Research Papers (<?php echo $result_count; ?>)</h3>
                    <?php if (empty($results)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-upload fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted">No papers yet</h4>
                            <p class="text-muted mb-4">Be the first to upload!</p>
                            <a href="upload.php" class="btn btn-primary btn-lg">Upload Paper</a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($results as $paper): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <span class="badge bg-success">Public</span>
                                                <span class="text-muted small"><?php echo $paper['publication_year'] ?: date('Y', strtotime($paper['created_at'])); ?></span>
                                            </div>
                                            <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($paper['title'] ?: 'Untitled'); ?></h6>
                                            <p class="text-muted small mb-3">
                                                <?php echo substr(htmlspecialchars($paper['abstract'] ?? ''), 0, 120) . '...'; ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted"><?php echo htmlspecialchars($paper['first_name'] . ' ' . $paper['last_name']); ?></small>
                                                <a href="summarize.php?paper_id=<?php echo $paper['id']; ?>" class="btn btn-primary btn-sm">
                                                    View
                                                </a>
                                                <span class="badge bg-secondary align-self-center ms-2">
                                                    <?php echo $paper['view_count']; ?> view<?php echo $paper['view_count'] != 1 ? 's' : ''; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- PAGINATION -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="All papers pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>