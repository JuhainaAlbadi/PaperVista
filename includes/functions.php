<?php
// Common helper functions

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return strlen($password) >= 8;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header("Location: login.php");
        exit();
    }
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function generateSlug($text) {
    // Convert to lowercase and replace spaces with hyphens
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function alert($message, $type = 'info') {
    $alert_types = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];

    $class = $alert_types[$type] ?? 'alert-info';
    return "<div class='alert {$class} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

function getCurrentPage() {
    return basename($_SERVER['PHP_SELF'], '.php');
}

function isActivePage($page) {
    return getCurrentPage() === $page ? 'active' : '';
}



function logSearch($conn, $user_id, $query, $result_count) {
    $query = trim($query);
    if (empty($query)) return;

    $stmt = $conn->prepare("
        INSERT INTO search_logs (user_id, search_query, result_count) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE result_count = VALUES(result_count), created_at = NOW()
    ");
    $stmt->bind_param("isi", $user_id, $query, $result_count);
    $stmt->execute();
    $stmt->close();
}

function deletePaperAdmin($paper_id) {
    global $conn;

    // Get file
    $stmt = $conn->prepare("SELECT file_path FROM papers WHERE id = ?");
    $stmt->bind_param("i", $paper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $paper = $result->fetch_assoc();
    $stmt->close();

    if (!$paper) return ['success' => false, 'error' => 'Paper not found'];

    // Delete file
    if (file_exists($paper['file_path'])) unlink($paper['file_path']);

    // Delete summaries
    $stmt = $conn->prepare("DELETE FROM summaries WHERE paper_id = ?");
    $stmt->bind_param("i", $paper_id);
    $stmt->execute();
    $stmt->close();

    // Delete paper
    $stmt = $conn->prepare("DELETE FROM papers WHERE id = ?");
    $stmt->bind_param("i", $paper_id);
    $success = $stmt->execute();
    $stmt->close();

    return ['success' => $success];
}




?>
