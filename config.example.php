<?php
/**
 * PaperVista Configuration Example
 * 
 * Copy this file to includes/config.php and configure your settings.
 * Choose EITHER OpenAI (Option A) OR Azure OpenAI (Option B)
 */

// PHP Configuration for Large File Handling
@ini_set('memory_limit', '1024M');
@ini_set('max_execution_time', '300');
@ini_set('post_max_size', '100M');
@ini_set('upload_max_filesize', '100M');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "papervista";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'PaperVista');
define('SITE_URL', 'http://localhost/php_summarizer/');  // Update this!
define('UPLOAD_DIR', 'uploads/');

// ==============================================================================
// OPTION A: REGULAR OPENAI (RECOMMENDED - Simple Setup)
// ==============================================================================
// Get your API key from: https://platform.openai.com/api-keys
//
// Uncomment these lines to use regular OpenAI:
define('USE_AZURE_OPENAI', false);
define('OPENAI_API_KEY', 'sk-your-openai-api-key-here');  // Replace with your key

// ==============================================================================
// OPTION B: AZURE OPENAI (ENTERPRISE)
// ==============================================================================
// Get credentials from: https://portal.azure.com
//
// Uncomment these lines to use Azure OpenAI (comment out Option A above):
// define('USE_AZURE_OPENAI', true);
// define('AZURE_OPENAI_ENDPOINT', 'https://your-resource.openai.azure.com');
// define('AZURE_OPENAI_API_KEY', 'your-azure-api-key-here');
// define('AZURE_OPENAI_DEPLOYMENT', 'gpt-4o');  // Your deployment name
// define('AZURE_API_VERSION', '2024-02-01');
// ==============================================================================

// Test Mode (set to true to avoid API calls during development)
define('TEST_MODE', false);  // Set to true for testing without API calls

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
?>
