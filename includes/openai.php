<?php
/**
 * OpenAI/Azure OpenAI API Integration for PaperVista
 * Handles AI-powered summarization using GPT models
 */

// API Configuration
if (!defined('OPENAI_API_URL')) {
    define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
}
if (!defined('OPENAI_MODEL')) {
    define('OPENAI_MODEL', 'gpt-4o-mini');
}

/**
 * Generate AI summary for academic paper
 * 
 * @param string $text The text content to summarize
 * @param string $type Summary type: 'short', 'medium', or 'detailed'
 * @param string $title Optional paper title for context
 * @return array Result with 'success', 'content', 'word_count', 'processing_time', 'model'
 */
function generateSummary($text, $type = 'medium', $title = '') {
    $start_time = microtime(true);
    
    // TEST MODE: Return mock summary without using API
    if (defined('TEST_MODE') && TEST_MODE === true) {
        return generateMockSummary($text, $type, $start_time);
    }
    
    // Check if using Azure OpenAI
    if (defined('USE_AZURE_OPENAI') && USE_AZURE_OPENAI === true) {
        return generateAzureSummary($text, $type, $title, $start_time);
    }
    
    // Use regular OpenAI
    return generateOpenAISummary($text, $type, $title, $start_time);
}

/**
 * Generate a mock summary for testing purposes
 */
function generateMockSummary($text, $type, $start_time) {
    $summary_content = "This is a mock summary of type '{$type}'. The AI model is currently in test mode. ";
    $summary_content .= "The summary is based on the first 100 characters of the provided text: '" . substr($text, 0, 100) . "...'. ";
    $summary_content .= "This feature is intended for development and testing to avoid actual API calls and costs.";
    
    $processing_time = round(microtime(true) - $start_time, 2);
    $word_count = str_word_count($summary_content);
    
    return [
        'success' => true,
        'content' => $summary_content,
        'word_count' => $word_count,
        'processing_time' => $processing_time,
        'model' => 'test-model',
        'tokens_used' => 0,
        'provider' => 'Mock'
    ];
}

/**
 * Generate summary using Azure OpenAI
 */
function generateAzureSummary($text, $type, $title, $start_time) {
    // Validate Azure configuration
    if (!defined('AZURE_OPENAI_ENDPOINT') || !defined('AZURE_OPENAI_API_KEY') || !defined('AZURE_OPENAI_DEPLOYMENT')) {
        return [
            'success' => false,
            'error' => 'Azure OpenAI is not properly configured. Check includes/config.php'
        ];
    }
    
    // Validate input
    if (empty($text)) {
        return [
            'success' => false,
            'error' => 'No text content provided for summarization'
        ];
    }
    
    // Truncate text if too long
    $max_chars = 12000;
    if (strlen($text) > $max_chars) {
        $text = substr($text, 0, $max_chars) . '...';
    }
    
    // Build prompt
    $prompt = buildSummaryPrompt($text, $type, $title);
    
    // Set max tokens based on summary type - increased to ensure complete summaries
    $max_tokens = [
        'short' => 600,
        'medium' => 1200,
        'detailed' => 2500
    ][$type] ?? 1200;
    
    // Build Azure OpenAI URL
    $azure_url = AZURE_OPENAI_ENDPOINT . '/openai/deployments/' . AZURE_OPENAI_DEPLOYMENT . '/chat/completions?api-version=' . AZURE_API_VERSION;
    
    // Prepare API request
    $data = [
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are an expert academic researcher who specializes in summarizing research papers. Provide clear, accurate, and well-structured summaries that capture the essential findings, methodology, and implications of academic work. IMPORTANT: Always provide complete summaries. Never end mid-sentence. Ensure your summary has a proper conclusion.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => $max_tokens,
        'temperature' => 0.7
    ];
    
    // Make API request to Azure
    $ch = curl_init($azure_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . AZURE_OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Handle curl errors
    if ($curl_error) {
        return [
            'success' => false,
            'error' => 'API request failed: ' . $curl_error
        ];
    }
    
    // Parse response
    $result = json_decode($response, true);
    
    // Handle API errors
    if ($http_code !== 200) {
        $error_message = $result['error']['message'] ?? 'Unknown API error';
        return [
            'success' => false,
            'error' => 'Azure OpenAI API error: ' . $error_message,
            'http_code' => $http_code
        ];
    }
    
    // Extract summary content
    $summary_content = $result['choices'][0]['message']['content'] ?? '';
    
    if (empty($summary_content)) {
        return [
            'success' => false,
            'error' => 'No summary generated by AI'
        ];
    }
    
    // Calculate processing time
    $processing_time = round(microtime(true) - $start_time, 2);
    
    // Calculate word count
    $word_count = str_word_count($summary_content);
    
    return [
        'success' => true,
        'content' => trim($summary_content),
        'word_count' => $word_count,
        'processing_time' => $processing_time,
        'model' => AZURE_OPENAI_DEPLOYMENT,
        'tokens_used' => $result['usage']['total_tokens'] ?? 0,
        'provider' => 'Azure OpenAI'
    ];
}

/**
 * Generate summary using regular OpenAI
 */
function generateOpenAISummary($text, $type, $title, $start_time) {
    // Check for API key
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        return [
            'success' => false,
            'error' => 'OpenAI API key is not configured. Please set it in includes/config.php'
        ];
    }
    
    // Validate input
    if (empty($text)) {
        return [
            'success' => false,
            'error' => 'No text content provided for summarization'
        ];
    }
    
    // Truncate text if too long
    $max_chars = 12000;
    if (strlen($text) > $max_chars) {
        $text = substr($text, 0, $max_chars) . '...';
    }
    
    // Build prompt
    $prompt = buildSummaryPrompt($text, $type, $title);
    
    // Set max tokens - increased to ensure complete summaries
    $max_tokens = [
        'short' => 600,
        'medium' => 1200,
        'detailed' => 2500
    ][$type] ?? 1200;
    
    // Prepare API request
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are an expert academic researcher who specializes in summarizing research papers. Provide clear, accurate, and well-structured summaries that capture the essential findings, methodology, and implications of academic work. IMPORTANT: Always provide complete summaries. Never end mid-sentence. Ensure your summary has a proper conclusion.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => $max_tokens,
        'temperature' => 0.3,
        'top_p' => 0.9,
        'frequency_penalty' => 0.0,
        'presence_penalty' => 0.0
    ];
    
    // Make API request
    $ch = curl_init(OPENAI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Handle curl errors
    if ($curl_error) {
        return [
            'success' => false,
            'error' => 'API request failed: ' . $curl_error
        ];
    }
    
    // Parse response
    $result = json_decode($response, true);
    
    // Handle API errors
    if ($http_code !== 200) {
        $error_message = $result['error']['message'] ?? 'Unknown API error';
        return [
            'success' => false,
            'error' => 'OpenAI API error: ' . $error_message
        ];
    }
    
    // Extract summary content
    $summary_content = $result['choices'][0]['message']['content'] ?? '';
    
    if (empty($summary_content)) {
        return [
            'success' => false,
            'error' => 'No summary generated by AI'
        ];
    }
    
    // Calculate processing time
    $processing_time = round(microtime(true) - $start_time, 2);
    
    // Calculate word count
    $word_count = str_word_count($summary_content);
    
    return [
        'success' => true,
        'content' => trim($summary_content),
        'word_count' => $word_count,
        'processing_time' => $processing_time,
        'model' => OPENAI_MODEL,
        'tokens_used' => $result['usage']['total_tokens'] ?? 0,
        'provider' => 'OpenAI'
    ];
}

/**
 * Build appropriate prompt based on summary type
 * 
 * @param string $text The paper text
 * @param string $type Summary type
 * @param string $title Paper title
 * @return string The complete prompt
 */
function buildSummaryPrompt($text, $type, $title = '') {
    $title_context = $title ? "Paper Title: {$title}\n\n" : '';
    
    switch($type) {
        case 'short':
            return "{$title_context}Provide a brief 2-3 paragraph summary of this academic paper. Focus on:\n" .
                   "1. The main research question or objective\n" .
                   "2. Key findings and conclusions\n" .
                   "3. Significance of the work\n\n" .
                   "IMPORTANT: Provide a COMPLETE summary with a proper ending. Do not end mid-sentence.\n\n" .
                   "Paper content:\n{$text}";
                   
        case 'medium':
            return "{$title_context}Provide a comprehensive 5-7 paragraph summary of this academic paper. Include:\n" .
                   "1. Research background and objectives\n" .
                   "2. Methodology and approach\n" .
                   "3. Key results and findings\n" .
                   "4. Discussion and implications\n" .
                   "5. Conclusions and future work\n\n" .
                   "IMPORTANT: Provide a COMPLETE summary with a proper ending. Do not end mid-sentence.\n\n" .
                   "Paper content:\n{$text}";
                   
        case 'detailed':
            return "{$title_context}Provide a detailed, comprehensive summary of this academic paper. Include:\n" .
                   "1. Introduction and background context\n" .
                   "2. Detailed methodology and experimental setup\n" .
                   "3. In-depth analysis of results and findings\n" .
                   "4. Discussion of limitations and potential biases\n" .
                   "5. Comparison with related work\n" .
                   "6. Conclusion and suggestions for future research\n\n" .
                   "IMPORTANT: Provide a COMPLETE summary with a proper ending. Do not end mid-sentence.\n\n" .
                   "Paper content:\n{$text}";
                   
        default:
            // Fallback to medium summary if type is invalid
            return buildSummaryPrompt($text, 'medium', $title);
    }
}
?>
