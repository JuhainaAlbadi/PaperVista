<?php
/**
 * File Upload and Processing Handler for php_summarizer
 * Handles PDF, DOCX, TXT uploads + metadata extraction
 */

require_once 'config.php';

// Load Composer autoloader (for smalot/pdfparser)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB
define('ALLOWED_EXTENSIONS', ['pdf', 'docx', 'doc', 'txt']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/msword',
    'text/plain'
]);

/**
 * Main upload handler
 */
function handleFileUpload($file, $user_id) {
    global $conn;

    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large. Max: ' . formatFileSize(MAX_FILE_SIZE)];
    }

    if ($file['size'] == 0) {
        return ['success' => false, 'error' => 'File is empty'];
    }

    $filename = $file['name'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file format'];
    }

    $unique_filename = uniqid('paper_', true) . '.' . $extension;
    $upload_path = UPLOAD_DIR . $unique_filename;

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }

    // Extract text
    $text_extraction = extractTextFromFile($upload_path, $extension);
    if (!$text_extraction['success']) {
        unlink($upload_path);
        return $text_extraction;
    }
    $text_content = $text_extraction['text'];

    // Extract metadata
    $metadata = extractPaperMetadata($text_content, $upload_path, $file['name']);

    // Prepare DB insert
    $title = $metadata['title'] ?? 'Untitled Paper';
    $authors = $metadata['authors'] ?? '';
    $abstract = $metadata['abstract'] ?? '';
    $year = $metadata['publication_year'] ?? null;

    $stmt = $conn->prepare("INSERT INTO papers 
        (title, authors, abstract, publication_year, file_path, file_size, file_type, text_content, processing_status, uploaded_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)");

    $stmt->bind_param("ssssssisi", 
        $title, $authors, $abstract, $year, 
        $upload_path, $file['size'], $mime_type, $text_content, $user_id
    );

    if (!$stmt->execute()) {
        unlink($upload_path);
        return ['success' => false, 'error' => 'Database error: ' . $stmt->error];
    }

    $paper_id = $stmt->insert_id;
    $stmt->close();

    return [
        'success' => true,
        'paper_id' => $paper_id,
        'title' => $title,
        'text_length' => strlen($text_content)
    ];
}

/**
 * Extract text from file
 */
function extractTextFromFile($filepath, $extension) {
    switch (strtolower($extension)) {
        case 'pdf':  return extractTextFromPDF($filepath);
        case 'docx':
        case 'doc':  return extractTextFromDOCX($filepath);
        case 'txt':  return extractTextFromTXT($filepath);
        default:     return ['success' => false, 'error' => 'Unsupported type'];
    }
}

/**
 * PDF Text Extraction (with smalot/pdfparser)
 */
function extractTextFromPDF($filepath) {
    set_time_limit(300);
    ini_set('memory_limit', '1024M');

    try {
        if (class_exists('\Smalot\PdfParser\Parser')) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filepath);
            $text = $pdf->getText();
            $text = preg_replace('/\s+/', ' ', trim($text));
            if (!empty($text)) {
                return ['success' => true, 'text' => $text];
            }
        }
    } catch (Exception $e) {
        error_log("PDF Parser Error: " . $e->getMessage());
    }

    // Fallback: pdftotext
    if (commandExists('pdftotext')) {
        $output_file = $filepath . '.txt';
        $cmd = "pdftotext " . escapeshellarg($filepath) . " " . escapeshellarg($output_file);
        exec($cmd, $output, $return);
        if ($return === 0 && file_exists($output_file)) {
            $text = file_get_contents($output_file);
            unlink($output_file);
            if (!empty(trim($text))) {
                return ['success' => true, 'text' => trim(preg_replace('/\s+/', ' ', $text))];
            }
        }
    }

    return ['success' => false, 'error' => 'Could not extract text from PDF'];
}

function extractTextFromDOCX($filepath) {
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return ['success' => false, 'error' => 'Cannot open DOCX'];
    }
    $content = $zip->getFromName('word/document.xml');
    $zip->close();
    if ($content === false) {
        return ['success' => false, 'error' => 'No content in DOCX'];
    }
    $text = strip_tags($content);
    $text = preg_replace('/\s+/', ' ', trim($text));
    return !empty($text) ? ['success' => true, 'text' => $text] : ['success' => false, 'error' => 'Empty DOCX'];
}

function extractTextFromTXT($filepath) {
    $text = file_get_contents($filepath);
    if ($text === false) {
        return ['success' => false, 'error' => 'Cannot read TXT'];
    }
    $encoding = mb_detect_encoding($text, ['UTF-8', 'Windows-1252'], true);
    if ($encoding && $encoding !== 'UTF-8') {
        $text = mb_convert_encoding($text, 'UTF-8', $encoding);
    }
    return ['success' => true, 'text' => trim(preg_replace('/\s+/', ' ', $text))];
}

/**
 * Extract metadata: title, authors, abstract, year
 */
function extractPaperMetadata($text, $filepath, $original_filename) {
    $metadata = [];

    // METHOD 1: PDF Metadata (via smalot/pdfparser)
    if (pathinfo($filepath, PATHINFO_EXTENSION) === 'pdf' && class_exists('\Smalot\PdfParser\Parser')) {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filepath);
            $details = $pdf->getDetails();

            if (!empty($details['Title'])) {
                $metadata['title'] = trim($details['Title']);
            }
            if (!empty($details['Author'])) {
                $metadata['authors'] = trim($details['Author']);
            }
            if (!empty($details['Subject'])) {
                $metadata['abstract'] = trim($details['Subject']);
            }
            if (!empty($details['CreationDate'])) {
                $year = substr($details['CreationDate'], 0, 4);
                if (is_numeric($year)) $metadata['publication_year'] = $year;
            }
            if (!empty($metadata['title'])) {
                return $metadata;
            }
        } catch (Exception $e) {
            error_log("PDF metadata error: " . $e->getMessage());
        }
    }

    // METHOD 2: Title from first meaningful line
    $lines = array_slice(explode("\n", $text), 0, 20);
    $title = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) > 15 && strlen($line) < 300 && !preg_match('/^(abstract|keywords?|introduction|fig\.|table)/i', $line)) {
            $title = $line;
            break;
        }
    }
    $metadata['title'] = $title ?: cleanFilename($original_filename);

    // METHOD 3: Authors from text
    if (preg_match('/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3}(?:,\s*[A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3})*)\b/', substr($text, 0, 2000), $m)) {
        $metadata['authors'] = $m[1];
    }

    return $metadata;
}

function cleanFilename($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/[-_]+/', ' ', $name);
    $name = preg_replace('/\b\d{4}\b/', '', $name);
    $name = preg_replace('/\b(pdf|docx?|txt)\b/i', '', $name);
    $name = ucwords(trim($name));
    return $name ?: 'Untitled Paper';
}

function commandExists($cmd) {
    $os = strtoupper(substr(PHP_OS, 0, 3));
    exec(($os === 'WIN' ? 'where' : 'which') . " $cmd 2>&1", $output, $return);
    return $return === 0;
}

/**
 * Delete paper (optional helper)
 */
function deletePaper($paper_id, $user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT file_path FROM papers WHERE id = ? AND uploaded_by = ?");
    $stmt->bind_param("ii", $paper_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $paper = $result->fetch_assoc();
    $stmt->close();

    if ($paper && file_exists($paper['file_path'])) {
        unlink($paper['file_path']);
    }

    $stmt = $conn->prepare("DELETE FROM papers WHERE id = ? AND uploaded_by = ?");
    $stmt->bind_param("ii", $paper_id, $user_id);
    $success = $stmt->execute();
    $stmt->close();

    return ['success' => $success];
}


?>