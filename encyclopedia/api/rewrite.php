<?php
// At the top of rewrite.php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

function logError($message, $data = null) {
    $log_message = "[" . date('Y-m-d H:i:s') . "] Rewrite API Error: " . $message;
    if ($data) {
        $log_message .= "\nData: " . print_r($data, true);
    }

    // Try multiple logging methods
    error_log($log_message); // Standard error_log

    // Also log to a specific file we can easily find
    $specific_log = __DIR__ . '/rewrite_api.log';
    file_put_contents($specific_log, $log_message . "\n", FILE_APPEND);

    // Force flush the PHP-FPM log buffer
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

// Test logging immediately
logError("Logging test from rewrite.php startup", [
    'time' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'sapi' => php_sapi_name(),
    'error_log_setting' => ini_get('error_log'),
    'log_errors_setting' => ini_get('log_errors'),
    'error_reporting_setting' => ini_get('error_reporting')
]);

header('Content-Type: application/json');

 function rewriteContent($content, $model = 'gpt-4o', $max_tokens = 16384, $provider = 'openai') {
     // Get API keys from environment with validation
     $api_keys = [
         'openai' => getenv('OPENAI_API_KEY'),
         'groq' => getenv('GROQ_API_KEY'),
         'anthropic' => getenv('ANTHROPIC_API_KEY')
     ];

     // Validate provider
     if (!isset($api_keys[$provider])) {
         logError("Invalid provider specified: " . $provider);
         throw new Exception("Invalid provider");
     }

     // Validate API key exists
     if (empty($api_keys[$provider])) {
         logError("API key not configured for provider: " . $provider);
         throw new Exception("API key not configured for " . $provider);
     }

     // Provider-specific configurations
     $api_config = [
         'openai' => [
             'base_url' => 'https://api.openai.com/v1/chat/completions',
             'auth_header' => 'Bearer'
         ],
         'groq' => [
             'base_url' => 'https://api.groq.com/openai/v1/chat/completions',
             'auth_header' => 'Bearer'
         ],
         'anthropic' => [
             'base_url' => 'https://api.anthropic.com/v1/messages',
             'auth_header' => 'x-api-key'
         ]
     ];

     $provider_config = $api_config[$provider];
     $is_anthropic = $provider === 'anthropic';

     // Model-specific configurations
     $model_configs = [
         'gemma2-9b-it' => [
             'supports_system_message' => true,
             'token_param_name' => 'max_tokens',
             'supports_temperature' => true,
             'provider' => 'groq'
         ],
         // ... other model configs remain the same
     ];

     $model_config = $model_configs[$model] ?? $model_configs['default'];

     // Prepare headers
     $headers = [
         'Content-Type: application/json'
     ];

     // Add authentication header
     $auth_header = $provider_config['auth_header'] . ': ' . $api_keys[$provider];
     $headers[] = $auth_header;

     // Add Anthropic specific headers if needed
     if ($is_anthropic) {
         $headers[] = 'anthropic-version: 2023-06-01';
     }

     // Log headers (with redacted API key)
     logError("Request headers", [
         'headers' => array_map(function($header) {
             return preg_replace('/Bearer \S+/', 'Bearer [REDACTED]', $header);
         }, $headers)
     ]);

     // Prepare the request data
     $system_content = 'You are an expert encyclopedia editor. Your task is to rewrite content while maintaining complete factual accuracy. Do not add information, remove details, or make assumptions. Focus on improving clarity, structure, and academic tone while preserving the exact meaning and all specific details from the source material. The output should be at least as detailed as the input.';

     $prompt = "Rewrite the following text in an authoritative encyclopedia style. Your output MUST be equal to or longer than the input text - do not condense or summarize. Expand explanations where appropriate while maintaining absolute factual accuracy. Keep every single detail, data point, and nuance from the source. Structure the content with improved clarity and formal academic tone, but never sacrifice completeness for conciseness. You should elaborate on concepts where it adds clarity, use precise language, and maintain comprehensive coverage of all points in the original text. Here is the content to rewrite:\n\n";

     // Calculate token limit
     $estimated_input_tokens = ceil((strlen($prompt) + strlen($content)) / 4);
     $token_limit = min($estimated_input_tokens * 2 + 1000, intval($max_tokens));

     // Prepare messages array
     $messages = [];
     if ($model_config['supports_system_message']) {
         $messages[] = [
             'role' => 'system',
             'content' => $system_content
         ];
         $messages[] = [
             'role' => 'user',
             'content' => $prompt . $content
         ];
     } else {
         $messages[] = [
             'role' => 'user',
             'content' => $system_content . "\n\n" . $prompt . $content
         ];
     }

     // Prepare request data
     $data = [
         'model' => $model,
         'messages' => $messages
     ];

     if ($model_config['supports_temperature']) {
         $data['temperature'] = 0.3;
     }

     $data[$model_config['token_param_name']] = $token_limit;

     // Log the request data
     logError("API Request Configuration", [
         'provider' => $provider,
         'model' => $model,
         'base_url' => $provider_config['base_url'],
         'request_data' => $data
     ]);

     // Make the API request
     $ch = curl_init($provider_config['base_url']);
     curl_setopt_array($ch, [
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_POST => true,
         CURLOPT_POSTFIELDS => json_encode($data),
         CURLOPT_HTTPHEADER => $headers,
         CURLOPT_TIMEOUT => 30
     ]);

     $response = curl_exec($ch);
     $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

     // Handle CURL errors
     if (curl_errno($ch)) {
         $error = curl_error($ch);
         curl_close($ch);
         logError("CURL Error", ['error' => $error]);
         throw new Exception("API request failed: " . $error);
     }

     curl_close($ch);

     // Handle HTTP errors
     if ($http_status !== 200) {
         logError("API Error Response", [
             'status' => $http_status,
             'response' => $response
         ]);
         $error_message = "API request failed";
         if ($response) {
             $error_data = json_decode($response, true);
             if ($error_data && isset($error_data['error']['message'])) {
                 $error_message .= ": " . $error_data['error']['message'];
             }
         }
         throw new Exception($error_message);
     }

     // Parse the response
     $result = json_decode($response, true);
     if (!$result) {
         logError("Invalid JSON response", ['response' => $response]);
         throw new Exception("Invalid API response format");
     }

     // Handle different response formats
     if ($is_anthropic) {
         if (!isset($result['content'][0]['text'])) {
             logError("Invalid Anthropic response structure", ['response' => $result]);
             throw new Exception("Invalid response structure");
         }
         return $result['content'][0]['text'];
     } else {
         if (!isset($result['choices'][0]['message']['content'])) {
             logError("Invalid response structure", ['response' => $result]);
             throw new Exception("Invalid response structure");
         }
         return $result['choices'][0]['message']['content'];
     }
 }


// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = file_get_contents('php://input');
        logError("Received request", $input); // Log the incoming request

        $decoded = json_decode($input, true);
        if (!$decoded || !isset($decoded['content'])) {
            throw new Exception('No content provided or invalid JSON');
        }

        $model = $decoded['model'] ?? 'gpt-4o';
        $max_tokens = $decoded['max_tokens'] ?? 16384;
        $provider = $decoded['provider'] ?? 'openai';

        logError("Processing request", [
            'model' => $model,
            'max_tokens' => $max_tokens,
            'provider' => $provider,
            'content_length' => strlen($decoded['content'])
        ]);

        $rewritten = rewriteContent($decoded['content'], $model, $max_tokens, $provider);
        echo json_encode(['content' => $rewritten]);

    } catch (Exception $e) {
        logError("Exception occurred", [
          'message' => $e->getMessage(),
          'trace' => $e->getTraceAsString()
        ]);
        http_response_code(500);
        echo json_encode([
          'error' => $e->getMessage(),
          'details' => $e->getTraceAsString()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}