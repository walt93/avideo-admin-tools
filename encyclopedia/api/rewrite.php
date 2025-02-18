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

// Update the logError function to write to a specific file
function logError($message, $data = null) {
    $log_message = date('Y-m-d H:i:s') . " - Rewrite API: " . $message;
    if ($data) {
        $log_message .= "\nData: " . print_r($data, true);
    }
    $log_message .= "\n----------------------------------------\n";

    // Write to a specific file in the api directory
    file_put_contents(
        __DIR__ . '/rewrite_debug.log',
        $log_message,
        FILE_APPEND
    );
}

header('Content-Type: application/json');


 function rewriteContent($content, $model = 'gpt-4o', $max_tokens = 16384, $provider = 'openai') {
     // Get API keys from environment
     $openai_key = getenv('OPENAI_API_KEY');
     $groq_key = getenv('GROQ_API_KEY');
     $anthropic_key = getenv('ANTHROPIC_API_KEY');

     // Determine provider and API details
     $api_config = [
         'openai' => [
             'key' => $openai_key,
             'base_url' => 'https://api.openai.com/v1/chat/completions',
             'auth_header' => 'Bearer'
         ],
         'groq' => [
             'key' => $groq_key,
             'base_url' => 'https://api.groq.com/openai/v1/chat/completions',
             'auth_header' => 'Bearer'
         ],
         'anthropic' => [
             'key' => $anthropic_key,
             'base_url' => 'https://api.anthropic.com/v1/messages',
             'auth_header' => 'x-api-key'
         ]
     ];

     $is_anthropic = $provider === 'anthropic';
     $provider_config = $api_config[$provider] ?? $api_config['openai'];

     if (!$provider_config['key']) {
         logError("API key not found for provider: " . $provider);
         throw new Exception("API key not configured");
     }

     // Model-specific configurations
     $model_configs = [
         'o1-mini' => [
             'supports_system_message' => false,
             'token_param_name' => 'max_completion_tokens',
             'supports_temperature' => false
         ],
        'o1-preview' => [
            'supports_system_message' => false,
            'token_param_name' => 'max_completion_tokens',
            'supports_temperature' => false
        ],
        'claude-3-5-sonnet-20241022' => [
             'provider' => 'anthropic',
             'supports_system_message' => true,
             'token_param_name' => 'max_tokens',
             'supports_temperature' => true
         ],
         'claude-3-5-haiku-20241022' => [
             'provider' => 'anthropic',
             'supports_system_message' => true,
             'token_param_name' => 'max_tokens',
             'supports_temperature' => true
         ],
         'default' => [
             'supports_system_message' => true,
             'token_param_name' => 'max_tokens',
             'supports_temperature' => true
         ]
     ];

     $model_config = $model_configs[$model] ?? $model_configs['default'];

     $system_content = 'You are an expert encyclopedia editor. Your task is to rewrite content while maintaining complete factual accuracy. Do not add information, remove details, or make assumptions. Focus on improving clarity, structure, and academic tone while preserving the exact meaning and all specific details from the source material. The output should be at least as detailed as the input.';

     $prompt = "Rewrite the following text in an authoritative encyclopedia style. Your output MUST be equal to or longer than the input text - do not condense or summarize. Expand explanations where appropriate while maintaining absolute factual accuracy. Keep every single detail, data point, and nuance from the source. Structure the content with improved clarity and formal academic tone, but never sacrifice completeness for conciseness. You should elaborate on concepts where it adds clarity, use precise language, and maintain comprehensive coverage of all points in the original text. Here is the content to rewrite:\n\n";

     // Calculate approximate input tokens (rough estimate: 4 chars per token)
     $estimated_input_tokens = ceil((strlen($prompt) + strlen($content)) / 4);
     $token_limit = min($estimated_input_tokens * 2 + 1000, intval($max_tokens));

     // Prepare the request data based on provider
     if ($is_anthropic) {
         $data = [
             'model' => $model,
             'system' => $system_content,
             'messages' => [
                 [
                     'role' => 'user',
                     'content' => $prompt . $content
                 ]
             ],
             'max_tokens' => $token_limit
         ];
         if ($model_config['supports_temperature']) {
             $data['temperature'] = 0.3;
         }
     } else {
         // OpenAI/Groq style request
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

         $data = [
             'model' => $model,
             'messages' => $messages
         ];

         if ($model_config['supports_temperature']) {
             $data['temperature'] = 0.3;
         }

         $data[$model_config['token_param_name']] = $token_limit;
     }

     // Log the API request configuration
     logError("API Request Configuration", [
         'provider' => $provider,
         'model' => $model,
         'base_url' => $provider_config['base_url'],
         'request_data' => $data
     ]);

    // Add this right before the curl_init call
    if ($provider === 'groq') {
        logError("Groq API Call Debug", [
            'api_key_exists' => !empty($api_key),
            'api_key_length' => strlen($api_key),
            'api_key_prefix' => substr($api_key, 0, 4),
            'url' => $provider_config['base_url'],
            'headers' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . substr($api_key, 0, 4) . '...'
            ],
            'request_data' => array_merge($data, ['model' => $model])
        ]);

        // Test the exact curl command
        $curl_debug = "curl -X POST " . $provider_config['base_url'] . " \\\n" .
                     "  -H 'Authorization: Bearer " . substr($api_key, 0, 4) . "...' \\\n" .
                     "  -H 'Content-Type: application/json' \\\n" .
                     "  -d '" . json_encode($data, JSON_PRETTY_PRINT) . "'";

        logError("Equivalent curl command", $curl_debug);
    }

     $ch = curl_init($provider_config['base_url']);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
     curl_setopt($ch, CURLOPT_POST, true);
     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
     curl_setopt($ch, CURLOPT_HTTPHEADER, [
         'Content-Type: application/json',
         $provider_config['auth_header'] . ': ' . $provider_config['key']
     ]);

     if ($is_anthropic) {
         curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
             'anthropic-version: 2023-06-01',
             'Content-Type: application/json',
         ], [
             $provider_config['auth_header'] . ': ' . $provider_config['key']
         ]));
     }

     $response = curl_exec($ch);
     $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

     // Handle errors and response processing
     if (curl_errno($ch)) {
         $curl_error = curl_error($ch);
         logError("CURL Error", [
             'error' => $curl_error,
             'errno' => curl_errno($ch)
         ]);
         throw new Exception("CURL Error: " . $curl_error);
     }

     curl_close($ch);

     if ($http_status !== 200) {
         logError("API request failed with status " . $http_status);
         $error_message = "API request failed";
         if ($response) {
             $error_data = json_decode($response, true);
             if ($error_data && isset($error_data['error'])) {
                 $error_message .= ": " . $error_data['error']['message'];
             }
         }
         throw new Exception($error_message);
     }

     $result = json_decode($response, true);

     // Handle different response formats
     if ($is_anthropic) {
         if (!$result || !isset($result['content'][0]['text'])) {
             logError("Invalid Anthropic API response structure: " . print_r($result, true));
             throw new Exception("Invalid API response");
         }
         return $result['content'][0]['text'];
     } else {
         if (!$result || !isset($result['choices'][0]['message']['content'])) {
             logError("Invalid API response structure: " . print_r($result, true));
             throw new Exception("Invalid API response");
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