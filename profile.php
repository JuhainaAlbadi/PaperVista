<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
requireLogin();

$page_title = "Edit Profile";
$user_id = $_SESSION['user_id'];
$error = $success = '';

// Fetch current user data
$stmt = $conn->prepare("SELECT first_name, last_name, email, institution FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $institution = trim($_POST['institution']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name)) {
        $error = "First name and last name are required.";
    } else {
        // Update basic info
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, institution = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $first_name, $last_name, $institution, $user_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Update password if provided
        if (!empty($new_password)) {
            if (strlen($new_password) < 8) {
                $error = "New password must be at least 8 characters.";
            } elseif ($new_password !== $confirm_password) {
                $error = "New passwords do not match.";
            } else {
                // Verify current password
                $pwd_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $pwd_stmt->bind_param("i", $user_id);
                $pwd_stmt->execute();
                $hash = $pwd_stmt->get_result()->fetch_assoc()['password'];
                $pwd_stmt->close();

                if (password_verify($current_password, $hash)) {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $pwd_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $pwd_update->bind_param("si", $new_hash, $user_id);
                    $pwd_update->execute();
                    $pwd_update->close();
                    $success = "Profile and password updated successfully!";
                } else {
                    $error = "Current password is incorrect.";
                }
            }
        } else {
            $success = "Profile updated successfully!";
        }

        // Refresh session data
        if (!$error) {
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name']  = $last_name;
            $user = array_merge($user, ['first_name' => $first_name, 'last_name' => $last_name, 'institution' => $institution]);
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h3 class="mb-0">
                        <i class="fas fa-user-edit text-primary me-2"></i>Edit Profile
                    </h3>
                </div>
                <div class="card-body p-5">

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control form-control-lg" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control form-control-lg" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control form-control-lg bg-light" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Institution / University</label>
                                <input type="text" name="institution" class="form-control form-control-lg" value="<?php echo htmlspecialchars($user['institution'] ?? ''); ?>" placeholder="e.g., Harvard University">
                            </div>

                            <div class="col-12 mt-5">
                                <hr>
                                <h5 class="mb-4">Change Password <small class="text-muted">(Leave blank to keep current)</small></h5>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Current Password</label>
                                <input type="password" name="current_password" class="form-control form-control-lg" placeholder="Required to change password">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">New Password</label>
                                <input type="password" name="new_password" class="form-control form-control-lg" placeholder="Min. 8 characters">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control form-control-lg" placeholder="Repeat new password">
                            </div>
                        </div>

                        <div class="mt-5 text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-lg px-5 ms-3">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>