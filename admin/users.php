<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = "Manage Users";
$success = '';
$error = '';

// Handle user role update
if (isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = sanitizeInput($_POST['role']);
    
    if (in_array($new_role, ['user', 'admin'])) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);
        
        if ($stmt->execute()) {
            $success = "User role updated successfully to " . ucfirst($new_role) . ".";
        } else {
            $error = "Failed to update user role: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Invalid role selected.";
    }
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    
    // Don't allow deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete user's summaries first
            $stmt = $conn->prepare("DELETE FROM summaries WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $summaries_deleted = $stmt->affected_rows;
            $stmt->close();
            
            // Delete user's papers
            $stmt = $conn->prepare("DELETE FROM papers WHERE uploaded_by = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $papers_deleted = $stmt->affected_rows;
            $stmt->close();
            
            // Delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            $success = "User deleted successfully! (Removed: $summaries_deleted summaries, $papers_deleted papers)";
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = "Failed to delete user: " . $e->getMessage();
        }
    }
}

// Get users with statistics
$users = [];
$result = $conn->query("
    SELECT u.*,
    (SELECT COUNT(*) FROM papers WHERE uploaded_by = u.id) as paper_count,
    (SELECT COUNT(*) FROM summaries WHERE user_id = u.id) as summary_count
    FROM users u
    ORDER BY u.created_at DESC
");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold">Manage Users</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Admin</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if ($success): ?>
        <?php echo alert($success, 'success'); ?>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <?php echo alert($error, 'error'); ?>
    <?php endif; ?>
    
    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Institution</th>
                            <th>Role</th>
                            <th>Papers</th>
                            <th>Summaries</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['institution'] ?? '-'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><span class="badge bg-info"><?php echo $user['paper_count']; ?></span></td>
                            <td><span class="badge bg-success"><?php echo $user['summary_count']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php echo $user['last_login_at'] ? date('M d, Y', strtotime($user['last_login_at'])) : 'Never'; ?>
                            </td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <div class="btn-group btn-group-sm">
                                    <!-- Inline Role Update Form -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="update_role" value="1">
                                        <select name="role" class="form-select form-select-sm" style="width: 100px; display: inline-block; margin-right: 5px;">
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <button type="submit" class="btn btn-outline-primary btn-sm" title="Update Role">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    
                                    <!-- Inline Delete Form -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user and all their data?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="delete_user" value="1">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span class="badge bg-secondary">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
