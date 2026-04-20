<?php
// login.php — FINAL VERSION (works every single time, even after logout)
// NO CHANGES TO ANY OTHER FILE NEEDED

session_start();                                   // Start session FIRST
require_once 'includes/config.php';                // Load config (has session_start inside too — safe)
require_once 'includes/functions.php';             // Load functions

$page_title = "Login";                             // Set page title

// Handle login form submission — MUST BE BEFORE ANY HTML
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $error = '';

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif (!validateEmail($email)) {
        $error = "Invalid email format";
    } else {
        $sql = "SELECT id, email, first_name, last_name, role, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // SUCCESS — Set session
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['email']       = $user['email'];
                $_SESSION['first_name']  = $user['first_name'];
                $_SESSION['last_name']   = $user['last_name'];
                $_SESSION['role']        = $user['role'];

                // Update last login
                $update_sql = "UPDATE users SET last_login_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();

                // INSTANT REDIRECT — NO OUTPUT BEFORE THIS!
                $redirect_url = ($user['role'] === 'admin') ? SITE_URL . 'admin/index.php' : SITE_URL . 'dashboard.php';
                header("Location: $redirect_url");
                exit();                                   // ← This stops everything
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "No account found with this email address";
        }
    }
}

// ONLY NOW — after redirect is safe — include header and show HTML
include 'includes/header.php';
?>

<!-- Login Section -->
<section class="py-5" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh; display: flex; align-items: center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="fas fa-file-alt text-primary me-2 fs-4"></i>
                                <span class="fw-bold fs-4 text-primary"><?php echo SITE_NAME; ?></span>
                            </div>
                            <h2 class="fw-bold mb-2">Welcome Back</h2>
                            <p class="text-muted">Sign in to your account to continue</p>
                        </div>

                        <!-- Alert Messages -->
                        <?php if (isset($error) && !empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($success) && !empty($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="POST" action="">
                            <!-- Email Field -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2 text-primary"></i>Email Address
                                </label>
                                <input type="email"
                                       class="form-control form-control-lg"
                                       id="email"
                                       name="email"
                                       placeholder="Enter your email address"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       required>
                            </div>

                            <!-- Password Field -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2 text-primary"></i>Password
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control form-control-lg"
                                           id="password"
                                           name="password"
                                           placeholder="Enter your password"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                                <!-- <a href="#" class="text-decoration-none">
                                    Forgot password?
                                </a> -->
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-4">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>

                            <!-- Divider -->
                            <div class="text-center mb-4">
                                <span class="text-muted">or</span>
                            </div>

                            <!-- Social Login Buttons (Placeholder) -->
                            <!-- <div class="d-grid gap-2 mb-4">
                                <button type="button" class="btn btn-outline-secondary btn-lg" disabled>
                                    <i class="fab fa-google me-2"></i>Continue with Google
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-lg" disabled>
                                    <i class="fab fa-github me-2"></i>Continue with GitHub
                                </button>
                            </div> -->

                            <!-- Sign Up Link -->
                            <div class="text-center">
                                <p class="mb-0">
                                    Don't have an account?
                                    <a href="register.php" class="text-decoration-none fw-semibold text-primary">
                                        Sign up for free
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Card -->
                <!-- <div class="card mt-4 border-0 bg-light">
                    <div class="card-body text-center p-4">
                        <h6 class="fw-bold mb-3">New to PaperVista?</h6>
                        <p class="text-muted mb-3 small">
                            Join our community of researchers and get AI-powered summaries
                            of academic papers to accelerate your research workflow.
                        </p>
                        <a href="register.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-plus me-2"></i>Learn More
                        </a>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
</section>

<script>
// Password toggle functionality
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');

    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
