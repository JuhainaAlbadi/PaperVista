<?php
/**
 * Test Upload Functionality
 * Tests document upload and text extraction for PDF, DOCX, and TXT files
 */

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/upload.php';

// Load Composer autoloader if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Start session
session_start();

// Set a test user ID (you can change this to an actual user ID from your database)
$test_user_id = 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Upload Functionality - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .test-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .test-result {
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .test-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .test-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .test-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .test-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            max-height: 300px;
            overflow-y: auto;
        }
        .method-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
        }
        .method-library {
            background: #28a745;
            color: white;
        }
        .method-cli {
            background: #17a2b8;
            color: white;
        }
        .method-fallback {
            background: #ffc107;
            color: #333;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-card">
            <h1 class="mb-4">
                <i class="fas fa-vial text-primary"></i> Document Upload Test Suite
            </h1>
            <p class="text-muted">Test the upload and text extraction functionality for PDF, DOCX, and TXT files.</p>
        </div>

        <?php
        // System Check
        echo '<div class="test-card">';
        echo '<h3><i class="fas fa-check-circle text-success"></i> System Check</h3>';
        
        $checks = [];
        
        // Check PHP version
        $php_version = phpversion();
        $checks[] = [
            'name' => 'PHP Version',
            'status' => version_compare($php_version, '7.4.0', '>='),
            'message' => $php_version,
            'required' => '7.4.0+'
        ];
        
        // Check upload directory
        $upload_dir_exists = is_dir(UPLOAD_DIR);
        $upload_dir_writable = is_writable(UPLOAD_DIR);
        $checks[] = [
            'name' => 'Upload Directory',
            'status' => $upload_dir_exists && $upload_dir_writable,
            'message' => UPLOAD_DIR . ($upload_dir_exists ? ($upload_dir_writable ? ' (writable)' : ' (not writable)') : ' (not found)'),
            'required' => 'Exists and writable'
        ];
        
        // Check Composer autoloader
        $composer_loaded = class_exists('\Smalot\PdfParser\Parser');
        $checks[] = [
            'name' => 'Composer Autoloader',
            'status' => file_exists(__DIR__ . '/vendor/autoload.php'),
            'message' => file_exists(__DIR__ . '/vendor/autoload.php') ? 'Found' : 'Not found',
            'required' => 'vendor/autoload.php'
        ];
        
        // Check PDF parser
        $checks[] = [
            'name' => 'PDF Parser Library',
            'status' => $composer_loaded,
            'message' => $composer_loaded ? 'smalot/pdfparser loaded' : 'Not available',
            'required' => 'smalot/pdfparser'
        ];
        
        // Check database connection
        try {
            $db_check = new PDO("mysql:host=" . $servername . ";dbname=" . $dbname, $username, $password);
            $db_status = true;
            $db_message = 'Connected to ' . $dbname;
        } catch (PDOException $e) {
            $db_status = false;
            $db_message = 'Connection failed: ' . $e->getMessage();
        }
        $checks[] = [
            'name' => 'Database Connection',
            'status' => $db_status,
            'message' => $db_message,
            'required' => 'MySQL connection'
        ];
        
        // Check ZipArchive for DOCX
        $zip_available = class_exists('ZipArchive');
        $checks[] = [
            'name' => 'ZipArchive Extension',
            'status' => $zip_available,
            'message' => $zip_available ? 'Available' : 'Not available',
            'required' => 'For DOCX processing'
        ];
        
        echo '<div class="stats-grid">';
        foreach ($checks as $check) {
            $icon = $check['status'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
            echo '<div class="stat-item">';
            echo '<i class="fas ' . $icon . ' fa-2x"></i>';
            echo '<div class="stat-label">' . htmlspecialchars($check['name']) . '</div>';
            echo '<div style="font-size: 12px; color: #6c757d; margin-top: 5px;">' . htmlspecialchars($check['message']) . '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
        
        // Handle file upload test
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
            echo '<div class="test-card">';
            echo '<h3><i class="fas fa-flask text-info"></i> Test Results</h3>';
            
            $file = $_FILES['test_file'];
            $start_time = microtime(true);
            
            echo '<div class="test-info">';
            echo '<strong>Testing file:</strong> ' . htmlspecialchars($file['name']) . '<br>';
            echo '<strong>Size:</strong> ' . number_format($file['size'] / 1024, 2) . ' KB<br>';
            echo '<strong>Type:</strong> ' . htmlspecialchars($file['type']);
            echo '</div>';
            
            // Test the upload
            $result = handleFileUpload($file, $test_user_id);
            
            $end_time = microtime(true);
            $processing_time = round(($end_time - $start_time), 2);
            
            if ($result['success']) {
                echo '<div class="test-result test-success">';
                echo '<h4><i class="fas fa-check-circle"></i> Upload Successful!</h4>';
                echo '<p><strong>Paper ID:</strong> ' . $result['paper_id'] . '</p>';
                echo '<p><strong>Processing Time:</strong> ' . $processing_time . ' seconds</p>';
                
                // Show extraction method used
                if (isset($result['extraction_method'])) {
                    $method_class = 'method-fallback';
                    if (strpos($result['extraction_method'], 'pdfparser') !== false) {
                        $method_class = 'method-library';
                    } elseif (strpos($result['extraction_method'], 'pdftotext') !== false) {
                        $method_class = 'method-cli';
                    }
                    echo '<p><strong>Extraction Method:</strong> <span class="method-badge ' . $method_class . '">' . htmlspecialchars($result['extraction_method']) . '</span></p>';
                }
                
                echo '<div class="stats-grid">';
                
                // Text stats
                if (isset($result['text'])) {
                    $text_length = strlen($result['text']);
                    $word_count = str_word_count($result['text']);
                    
                    echo '<div class="stat-item">';
                    echo '<div class="stat-value">' . number_format($text_length) . '</div>';
                    echo '<div class="stat-label">Characters Extracted</div>';
                    echo '</div>';
                    
                    echo '<div class="stat-item">';
                    echo '<div class="stat-value">' . number_format($word_count) . '</div>';
                    echo '<div class="stat-label">Words Extracted</div>';
                    echo '</div>';
                    
                    echo '<div class="stat-item">';
                    echo '<div class="stat-value">' . $processing_time . 's</div>';
                    echo '<div class="stat-label">Processing Time</div>';
                    echo '</div>';
                }
                
                echo '</div>';
                
                // Show extracted text preview
                if (isset($result['text']) && !empty($result['text'])) {
                    echo '<h5 class="mt-4">Extracted Text Preview (first 1000 characters):</h5>';
                    echo '<div class="code-block">';
                    echo htmlspecialchars(substr($result['text'], 0, 1000));
                    if (strlen($result['text']) > 1000) {
                        echo "\n\n... (+" . number_format(strlen($result['text']) - 1000) . " more characters)";
                    }
                    echo '</div>';
                } else {
                    echo '<div class="test-warning mt-3">';
                    echo '<i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> No text was extracted from the file.';
                    echo '</div>';
                }
                
                // Show database record
                echo '<h5 class="mt-4">Database Record:</h5>';
                echo '<div class="code-block">';
                try {
                    $db_conn = new PDO("mysql:host=" . $servername . ";dbname=" . $dbname, $username, $password);
                    $stmt = $db_conn->prepare("SELECT * FROM papers WHERE paper_id = ?");
                    $stmt->execute([$result['paper_id']]);
                    $paper = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo '<pre>' . print_r($paper, true) . '</pre>';
                } catch (PDOException $e) {
                    echo 'Database error: ' . htmlspecialchars($e->getMessage());
                }
                echo '</div>';
                
                echo '<div class="mt-3">';
                echo '<a href="summarize.php?id=' . $result['paper_id'] . '" class="btn btn-primary">';
                echo '<i class="fas fa-robot"></i> Generate Summary</a> ';
                echo '<a href="dashboard.php" class="btn btn-secondary">';
                echo '<i class="fas fa-tachometer-alt"></i> View Dashboard</a>';
                echo '</div>';
                
                echo '</div>';
            } else {
                echo '<div class="test-result test-error">';
                echo '<h4><i class="fas fa-times-circle"></i> Upload Failed</h4>';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($result['message']) . '</p>';
                echo '<p><strong>Processing Time:</strong> ' . $processing_time . ' seconds</p>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>

        <!-- Upload Form -->
        <div class="test-card">
            <h3><i class="fas fa-upload text-primary"></i> Test File Upload</h3>
            <p class="text-muted">Upload a test document to verify the extraction functionality.</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="test_file" class="form-label">Select Test File</label>
                    <input type="file" class="form-control" id="test_file" name="test_file" 
                           accept=".pdf,.docx,.doc,.txt" required>
                    <div class="form-text">
                        Supported formats: PDF, DOCX, DOC, TXT (Max: 50MB)
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-vial"></i> Run Test
                </button>
                
                <a href="dashboard.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-home"></i> Back to Dashboard
                </a>
            </form>
        </div>

        <!-- Test Sample Files Section -->
        <div class="test-card">
            <h3><i class="fas fa-file-alt text-info"></i> Create Test Files</h3>
            <p class="text-muted">Generate sample test files if you don't have any.</p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5>TXT Test File</h5>
                            <p class="small">Create a sample text file</p>
                            <a href="create_test_file.php?type=txt" class="btn btn-sm btn-outline-primary">
                                Generate TXT
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                            <h5>PDF Test</h5>
                            <p class="small">Upload your own PDF file</p>
                            <button class="btn btn-sm btn-outline-danger" disabled>
                                Manual Upload
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-file-word fa-3x text-primary mb-3"></i>
                            <h5>DOCX Test</h5>
                            <p class="small">Upload your own DOCX file</p>
                            <button class="btn btn-sm btn-outline-primary" disabled>
                                Manual Upload
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documentation -->
        <div class="test-card">
            <h3><i class="fas fa-book text-warning"></i> Testing Guide</h3>
            
            <div class="accordion" id="testingGuide">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#testPDF">
                            Testing PDF Files
                        </button>
                    </h2>
                    <div id="testPDF" class="accordion-collapse collapse show" data-bs-parent="#testingGuide">
                        <div class="accordion-body">
                            <strong>PDF Extraction Methods (in order of priority):</strong>
                            <ol>
                                <li><span class="method-badge method-library">smalot/pdfparser</span> - Primary method using Composer library</li>
                                <li><span class="method-badge method-cli">pdftotext CLI</span> - Command-line utility (if installed)</li>
                                <li><span class="method-badge method-fallback">Binary Fallback</span> - Last resort text extraction</li>
                            </ol>
                            <p class="mt-3"><strong>What to test:</strong></p>
                            <ul>
                                <li>Upload a text-based PDF (digital document)</li>
                                <li>Verify text is extracted correctly</li>
                                <li>Check which extraction method was used</li>
                                <li>Generate summary from extracted text</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#testDOCX">
                            Testing DOCX Files
                        </button>
                    </h2>
                    <div id="testDOCX" class="accordion-collapse collapse" data-bs-parent="#testingGuide">
                        <div class="accordion-body">
                            <strong>DOCX uses ZipArchive to extract text from XML content.</strong>
                            <p class="mt-3"><strong>What to test:</strong></p>
                            <ul>
                                <li>Upload a DOCX file with text content</li>
                                <li>Verify text and formatting are preserved</li>
                                <li>Check paragraph structure</li>
                                <li>Generate summary</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#testTXT">
                            Testing TXT Files
                        </button>
                    </h2>
                    <div id="testTXT" class="accordion-collapse collapse" data-bs-parent="#testingGuide">
                        <div class="accordion-body">
                            <strong>TXT files are read directly with encoding detection.</strong>
                            <p class="mt-3"><strong>What to test:</strong></p>
                            <ul>
                                <li>Upload a plain text file</li>
                                <li>Verify text is read correctly</li>
                                <li>Check encoding handling (UTF-8, ASCII, etc.)</li>
                                <li>Generate summary</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
