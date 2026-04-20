<?php
$page_title = "Home";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="row" style="min-height: calc(100vh - 120px);">
            <div class="col-lg-6">
                <div class="hero-content">
                    <h1 class="fw-bold mb-4" style="line-height: 1.15; color: #1e3a8a; font-size: 3.5rem;">
                        Explore<br>Knowledge,<br>Empower<br>Research
                    </h1>
                    <p class="lead mb-5" style="font-size: 1rem; color: #4b5563; max-width: 450px;">
                        Search through a curated collection of research papers published by university scholars.
                    </p>

                    <!-- Search Card -->
                    <div class="search-card">
                        <h3 class="mb-4 fw-bold" style="letter-spacing: 1.5px; color: white; font-size: 0.85rem;">START SEARCHING</h3>
                        <form method="GET" action="search.php">
                            <div class="search-input-wrapper mb-3">
                                <input type="text"
                                       name="q"
                                       class="form-control"
                                       style="border: 2px solid white; padding: 0.9rem 2.5rem 0.9rem 1rem; border-radius: 4px; background: transparent; color: white;"
                                       required>
                                <i class="fas fa-search search-icon" style="color: white;"></i>
                            </div>
                            <button type="submit" class="btn btn-light w-100 py-3" style="border-radius: 4px; font-weight: 600; letter-spacing: 0.5px; color: #1e3a8a;">
                                SEARCH
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Side - Illustration -->
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-illustration-wrapper">
                    <img src="<?php echo SITE_URL; ?>image/Generated Image October 07, 2025 - 2_16PM.png" 
                         alt="Research Illustration" 
                         class="hero-illustration-image">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer Note -->
<footer class="footer-note-section">
    <div class="container">
        <p class="footer-note-text">
            © 2025 PaperVista | Designed for University Of Technology And Applied Science
        </p>
    </div>
</footer>

<?php include 'includes/footer.php'; ?>
