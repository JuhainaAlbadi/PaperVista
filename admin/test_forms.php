<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = "Test Forms";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border: 2px solid #333;'>";
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['update_role'])) {
        echo "<h3>Role Update Test:</h3>";
        $user_id = intval($_POST['user_id']);
        $new_role = $_POST['role'];
        
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Role update successful! Affected rows: " . $stmt->affected_rows . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Role update failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
    
    if (isset($_POST['delete_user'])) {
        echo "<h3>Delete User Test:</h3>";
        $user_id = intval($_POST['user_id']);
        echo "<p>Would delete user ID: $user_id (not actually deleting in test mode)</p>";
    }
    
    echo "</div>";
}

include '../includes/header.php';
?>

<div class="container py-4">
    <h2>Form Submission Test Page</h2>
    
    <div class="card mb-4">
        <div class="card-body">
            <h4>Test Role Update</h4>
            <form method="POST">
                <input type="hidden" name="user_id" value="2">
                <select name="role" class="form-select mb-2">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="update_role" class="btn btn-primary">Update Role</button>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <h4>Test Delete User</h4>
            <form method="POST">
                <input type="hidden" name="user_id" value="3">
                <button type="submit" name="delete_user" class="btn btn-danger">Test Delete (No actual deletion)</button>
            </form>
        </div>
    </div>
    
    <a href="users.php" class="btn btn-secondary">Back to Users</a>
</div>

<?php include '../includes/footer.php'; ?>
