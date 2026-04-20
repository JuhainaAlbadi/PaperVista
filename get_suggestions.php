<?php
// get_suggestions.php - DB + HARDCODED FALLBACK
header('Content-Type: application/json');

$q = strtolower(trim($_POST['q'] ?? ''));
if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

// === 1. HARDCODED SUGGESTIONS (always work) ===
$fallback = [
    "machine learning",
    "neural networks",
    "deep learning",
    "malware analysis",
    "malicious code",
    "malaria research",
    "data science",
    "artificial intelligence"
];

// === 2. TRY DB (if config exists) ===
$db_suggestions = [];

$config_path = __DIR__ . '/includes/config.php';
if (file_exists($config_path)) {
    require_once $config_path;

    if (isset($conn) && !$conn->connect_error) {
        $like = '%' . $conn->real_escape_string($q) . '%';
        $stmt = $conn->prepare("
            SELECT search_query FROM search_logs
             WHERE LOWER(search_query) LIKE ?
            ORDER BY 
            created_at DESC LIMIT 8
        ");
        if ($stmt) {
            $stmt->bind_param("s", $like);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $db_suggestions[] = $row['search_query'];
            }
            $stmt->close();
        }
    }
}

// === 3. MERGE: DB first, then fallback ===
$all = array_merge($db_suggestions, $fallback);

// === 4. FILTER & DEDUPLICATE ===
$seen = [];
$final = [];
foreach ($all as $item) {
    $lower = strtolower($item);
    if (strpos($lower, $q) !== false && !isset($seen[$lower])) {
        $seen[$lower] = true;
        $final[] = $item;
    }
}

echo json_encode(array_slice($final, 0, 8));
exit;
?>