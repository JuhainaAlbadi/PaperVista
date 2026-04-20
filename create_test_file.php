<?php
/**
 * Create Test Files
 * Generates sample test files for testing upload functionality
 */

$type = $_GET['type'] ?? 'txt';

if ($type === 'txt') {
    // Generate sample TXT file
    $content = <<<EOT
Sample Academic Paper for Testing
Author: Test User
Date: October 19, 2025

Abstract
This is a sample academic paper created for testing the PaperVista AI Summarization System. 
The purpose of this document is to verify that the text extraction and AI summarization 
features are working correctly.

Introduction
Academic research papers are essential documents in the field of higher education and 
scientific discovery. They serve as a medium for sharing new findings, methodologies, 
and theoretical frameworks with the broader academic community.

The ability to quickly summarize and extract key insights from research papers has become 
increasingly important in today's information-rich environment. This is where AI-powered 
summarization tools like PaperVista become invaluable.

Methodology
Our testing methodology involves the following steps:
1. Upload test documents in various formats (PDF, DOCX, TXT)
2. Verify text extraction accuracy
3. Generate AI summaries using OpenAI GPT models
4. Evaluate summary quality and relevance
5. Test export functionality across multiple formats

Results
The system successfully processes documents through multiple extraction methods:
- PDF files are processed using smalot/pdfparser library
- DOCX files are extracted using ZipArchive
- TXT files are read directly with encoding detection

Each method has been tested and verified to work correctly with appropriate fallback 
mechanisms in place for edge cases.

Discussion
The implementation demonstrates robust error handling and graceful degradation. When 
the primary extraction method fails, the system automatically attempts alternative 
methods to ensure maximum compatibility.

This multi-layered approach ensures that users can upload and process documents 
regardless of their specific format variations or creation tools.

Conclusion
The PaperVista AI Summarization System successfully handles document processing across 
multiple formats. The text extraction is accurate, the AI integration is functional, 
and the overall user experience is streamlined and efficient.

Future work may include support for additional file formats, enhanced OCR capabilities 
for scanned documents, and improved handling of complex document structures.

References
[1] Sample Reference 1
[2] Sample Reference 2
[3] Sample Reference 3

This document contains approximately 350 words and serves as an excellent test case 
for the summarization system.
EOT;

    // Set headers for download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="test_paper_sample.txt"');
    header('Content-Length: ' . strlen($content));
    
    echo $content;
    exit;
}

// Redirect back if invalid type
header('Location: test_upload.php');
exit;
