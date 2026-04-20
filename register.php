<?php
ob_start();                   // ← THIS IS THE ONLY LINE THAT FIXES THE ERROR
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Register";

// Handle registration form submission — STILL BEFORE header.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $institution = sanitizeInput($_POST['institution']);
    $error = '';
    $success = '';

    // === YOUR VALIDATION CODE (unchanged) ===
    if (empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name)) {
        $error = "Please fill in all required fields";
    } elseif (!validateEmail($email)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "An account with this email already exists";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (email, password, first_name, last_name, institution, role, created_at) VALUES (?, ?, ?, ?, ?, 'user', NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $email, $hashed_password, $first_name, $last_name, $institution);

            if ($insert_stmt->execute()) {
                $success = "Registration successful! Redirecting...";
                // Auto-login + instant redirect (no more header error)
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'user';

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// NOW safe to include header
include 'includes/header.php';
?>

<!-- Registration Section -->
<section class="py-5" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh; display: flex; align-items: center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <i class="fas fa-file-alt text-primary me-2 fs-4"></i>
                                <span class="fw-bold fs-4 text-primary"><?php echo SITE_NAME; ?></span>
                            </div>
                            <h2 class="fw-bold mb-2">Join PaperVista</h2>
                            <p class="text-muted">Create your account to start summarizing research papers</p>
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

                        <!-- Registration Form -->
                        <form method="POST" action="">
                            <div class="row">
                                <!-- First Name -->
                                <div class="col-md-6 mb-4">
                                    <label for="first_name" class="form-label fw-semibold">
                                        <i class="fas fa-user me-2 text-primary"></i>First Name *
                                    </label>
                                    <input type="text"
                                           class="form-control form-control-lg"
                                           id="first_name"
                                           name="first_name"
                                           placeholder="Enter your first name"
                                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                           required>
                                </div>

                                <!-- Last Name -->
                                <div class="col-md-6 mb-4">
                                    <label for="last_name" class="form-label fw-semibold">
                                        <i class="fas fa-user me-2 text-primary"></i>Last Name *
                                    </label>
                                    <input type="text"
                                           class="form-control form-control-lg"
                                           id="last_name"
                                           name="last_name"
                                           placeholder="Enter your last name"
                                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                           required>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2 text-primary"></i>Email Address *
                                </label>
                                <input type="email"
                                       class="form-control form-control-lg"
                                       id="email"
                                       name="email"
                                       placeholder="Enter your email address"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       required>
                            </div>

                            <!-- Institution -->
                            <div class="mb-4">
                                <label for="institution" class="form-label fw-semibold">
                                    <i class="fas fa-university me-2 text-primary"></i>Institution/Organization
                                </label>
                                <input type="text"
                                       class="form-control form-control-lg"
                                       id="institution"
                                       name="institution"
                                       placeholder="Enter your institution or organization"
                                       value="<?php echo isset($_POST['institution']) ? htmlspecialchars($_POST['institution']) : ''; ?>">
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2 text-primary"></i>Password *
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control form-control-lg"
                                           id="password"
                                           name="password"
                                           placeholder="Create a password (min. 8 characters)"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    Password must be at least 8 characters long
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2 text-primary"></i>Confirm Password *
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control form-control-lg"
                                           id="confirm_password"
                                           name="confirm_password"
                                           placeholder="Confirm your password"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the
                                        <a href="#" class="text-decoration-none text-primary">Terms of Service</a>
                                        and
                                        <a href="#" class="text-decoration-none text-primary">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-4">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>

                            <!-- Divider -->
                            <div class="text-center mb-4">
                                <span class="text-muted">or</span>
                            </div>

                            <!-- Social Registration Buttons (Placeholder)
                            <div class="d-grid gap-2 mb-4">
                                <button type="button" class="btn btn-outline-secondary btn-lg" disabled>
                                    <i class="fab fa-google me-2"></i>Sign up with Google
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-lg" disabled>
                                    <i class="fab fa-github me-2"></i>Sign up with GitHub
                                </button>
                            </div> -->

                            <!-- Login Link -->
                            <div class="text-center">
                                <p class="mb-0">
                                    Already have an account?
                                    <a href="login.php" class="text-decoration-none fw-semibold text-primary">
                                        Sign in here
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Benefits Card -->
                <div class="card mt-4 border-0 bg-light">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-center">Why Join PaperVista?</h6>
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-brain fa-2x text-primary mb-2"></i>
                                <h6 class="fw-bold">AI-Powered Summaries</h6>
                                <p class="text-muted small">Get intelligent summaries of research papers</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-upload fa-2x text-success mb-2"></i>
                                <h6 class="fw-bold">Easy Upload</h6>
                                <p class="text-muted small">Support for PDF, DOCX, and TXT files</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-search fa-2x text-info mb-2"></i>
                                <h6 class="fw-bold">Smart Search</h6>
                                <p class="text-muted small">Find relevant papers quickly</p>
                            </div>
                        </div>
                    </div>
                </div>
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

document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
    const password = document.getElementById('confirm_password');
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

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;

    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
