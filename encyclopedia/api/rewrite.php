<?php
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

function rewriteContent($content, $model = 'gpt-4o', $max_tokens = 16384, $provider = 'openai') {
    logError("Starting rewrite request", [
        'content_length' => strlen($content),
        'model' => $model,
        'max_tokens' => $max_tokens,
        'provider' => $provider
    ]);

    // Get API key
    $api_key = getProviderApiKey($provider);

    try {
        // Dispatch to provider-specific handler
        switch ($provider) {
            case 'openai':
                return handleOpenAIRequest($content, $model, $max_tokens, $api_key);
            case 'anthropic':
                return handleAnthropicRequest($content, $model, $max_tokens, $api_key);
            case 'groq':
                return handleGroqRequest($content, $model, $max_tokens, $api_key);
            default:
                throw new Exception("Unsupported provider: " . $provider);
        }
    } catch (Exception $e) {
        logError("Exception in rewriteContent", [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

function getProviderApiKey($provider) {
    $api_keys = [
        'openai' => getenv('OPENAI_API_KEY'),
        'groq' => getenv('GROQ_API_KEY'),
        'anthropic' => getenv('ANTHROPIC_API_KEY')
    ];

    if (!isset($api_keys[$provider])) {
        throw new Exception("Invalid provider");
    }

    if (empty($api_keys[$provider])) {
        throw new Exception("API key not configured for " . $provider);
    }

    return $api_keys[$provider];
}

function getProviderConfig($provider, $model) {
    $base_configs = [
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

    if (!isset($base_configs[$provider])) {
        throw new Exception("Invalid provider configuration");
    }

    return $base_configs[$provider];
}

function calculateTokenLimit($content, $max_tokens) {
    $estimated_input_tokens = ceil((strlen($content) + 500) / 4); // Add buffer for system/user message
    return min($estimated_input_tokens * 2 + 1000, intval($max_tokens));
}

function getOpenAIModelConfig($model) {
    $configs = [
        'gpt-4o' => [
            'supports_system_message' => true,
            'supports_temperature' => true,
            'token_param_name' => 'max_tokens'
        ],
        'gpt-4o-mini' => [
            'supports_system_message' => true,
            'supports_temperature' => true,
            'token_param_name' => 'max_tokens'
        ],
        'o1-mini' => [
            'supports_system_message' => false,
            'supports_temperature' => false,
            'token_param_name' => 'max_completion_tokens'
        ],
        'o1-preview' => [
            'supports_system_message' => false,
            'supports_temperature' => false,
            'token_param_name' => 'max_completion_tokens'
        ],
        // Add other models as needed
    ];

    if (!isset($configs[$model])) {
        throw new Exception("Unsupported OpenAI model: " . $model);
    }

    return $configs[$model];
}

function handleOpenAIRequest($content, $model, $max_tokens, $api_key) {
    logError("Handling OpenAI request", ['model' => $model]);

    $base_url = 'https://api.openai.com/v1/chat/completions';
    $model_config = getOpenAIModelConfig($model);

    // OpenAI-specific headers
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];

    // Build messages based on model capabilities
    $messages = [];
    if ($model_config['supports_system_message']) {
        $messages[] = [
            'role' => 'system',
            'content' => 'You are an expert encyclopedia editor. Your task is to rewrite content while maintaining complete factual accuracy. Do not add information, remove details, or make assumptions. Focus on improving clarity, structure, and academic tone while preserving the exact meaning and all specific details from the source material. The output should be at least as detailed as the input.'
        ];
    }

    // For models that don't support system messages, include instructions in user message
    $user_content = $model_config['supports_system_message']
        ? "Rewrite the following text in an authoritative encyclopedia style."
        : "You are an expert encyclopedia editor. Rewrite the following text in an authoritative encyclopedia style, maintaining complete factual accuracy.";

    $user_content .= " Your output MUST be equal to or longer than the input text - do not condense or summarize. Expand explanations where appropriate while maintaining absolute factual accuracy. Keep every single detail, data point, and nuance from the source. Structure the content with improved clarity and formal academic tone, but never sacrifice completeness for conciseness. You should elaborate on concepts where it adds clarity, use precise language, and maintain comprehensive coverage of all points in the original text. Here is the content to rewrite:\n\n" . $content;

    $messages[] = [
        'role' => 'user',
        'content' => $user_content
    ];

    // Build request data based on model capabilities
    $data = [
        'model' => $model,
        'messages' => $messages
    ];

    // Add temperature only if supported
    if ($model_config['supports_temperature']) {
        $data['temperature'] = 0.3;
    }

    // Add token limit with correct parameter name
    $data[$model_config['token_param_name']] = calculateTokenLimit($content, $max_tokens);

    logError("OpenAI request prepared", [
        'url' => $base_url,
        'model_config' => $model_config,
        'data_structure' => array_keys($data)
    ]);

    // Make request
    $response = makeApiRequest($base_url, $headers, $data);

    // Parse OpenAI-specific response
    if (!isset($response['choices'][0]['message']['content'])) {
        logError("Invalid OpenAI response structure", ['response' => $response]);
        throw new Exception("Invalid response structure from OpenAI API");
    }

    return $response['choices'][0]['message']['content'];
}

function handleGroqRequest($content, $model, $max_tokens, $api_key) {
    logError("Handling Groq request", ['model' => $model]);

    $base_url = 'https://api.groq.com/openai/v1/chat/completions';

    // Groq-specific headers
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];

    // Groq-specific message format
    $messages = [
        [
            'role' => 'system',
            'content' => 'You are an expert encyclopedia editor. Your task is to rewrite content while maintaining complete factual accuracy. Do not add information, remove details, or make assumptions. Focus on improving clarity, structure, and academic tone while preserving the exact meaning and all specific details from the source material. The output should be at least as detailed as the input.'
        ],
        [
            'role' => 'user',
            'content' => "Rewrite the following text in an authoritative encyclopedia style. Your output MUST be equal to or longer than the input text - do not condense or summarize. Expand explanations where appropriate while maintaining absolute factual accuracy. Keep every single detail, data point, and nuance from the source. Structure the content with improved clarity and formal academic tone, but never sacrifice completeness for conciseness. You should elaborate on concepts where it adds clarity, use precise language, and maintain comprehensive coverage of all points in the original text. Here is the content to rewrite:\n\n" . $content
        ]
    ];

    // Groq-specific request body
    $data = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.3,
        'max_tokens' => calculateTokenLimit($content, $max_tokens)
    ];

    logError("Groq request prepared", [
        'url' => $base_url,
        'data_structure' => array_keys($data)
    ]);

    // Make request
    $response = makeApiRequest($base_url, $headers, $data);

    // Parse Groq-specific response
    if (!isset($response['choices'][0]['message']['content'])) {
        logError("Invalid Groq response structure", ['response' => $response]);
        throw new Exception("Invalid response structure from Groq API");
    }

    return $response['choices'][0]['message']['content'];
}

function handleAnthropicRequest($content, $model, $max_tokens, $api_key) {
    logError("Handling Anthropic request", ['model' => $model]);

    $base_url = 'https://api.anthropic.com/v1/messages';

    // Anthropic-specific headers
    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01'
    ];

    // Anthropic-specific request body
    $data = [
        'model' => $model,
        'max_tokens' => calculateTokenLimit($content, $max_tokens),
        'messages' => [
            [
                'role' => 'user',
                'content' => "You are an expert encyclopedia editor. Rewrite the following content while maintaining complete factual accuracy. Do not add information, remove details, or make assumptions. Focus on improving clarity, structure, and academic tone while preserving the exact meaning and all specific details.\n\n" . $content
            ]
        ]
    ];

    logError("Anthropic request prepared", [
        'url' => $base_url,
        'data_structure' => array_keys($data)
    ]);

    // Make request
    $response = makeApiRequest($base_url, $headers, $data);

    // Parse Anthropic-specific response
    if (!isset($response['content'][0]['text'])) {
        logError("Invalid Anthropic response structure", ['response' => $response]);
        throw new Exception("Invalid response structure from Anthropic API");
    }

    return $response['content'][0]['text'];
}

function makeApiRequest($url, $headers, $data) {
    logError("Making API request", [
        'url' => $url,
        'headers' => array_map(function($header) {
            return preg_replace('/Bearer \S+|x-api-key: \S+/', '[REDACTED]', $header);
        }, $headers)
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    logError("API response received", [
        'status' => $http_status,
        'response_length' => strlen($response)
    ]);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        logError("CURL Error", ['error' => $error]);
        throw new Exception("API request failed: " . $error);
    }

    curl_close($ch);

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

    $result = json_decode($response, true);
    if (!$result) {
        logError("JSON decode failed", [
            'json_error' => json_last_error_msg(),
            'response' => $response
        ]);
        throw new Exception("Invalid API response format");
    }

    return $result;
}


// Read the raw POST data
$json = file_get_contents('php://input');
logError("Received request", ['raw_input' => $json]);

// Decode JSON request
$data = json_decode($json, true);
if (!$data) {
    logError("JSON decode failed", [
        'error' => json_last_error_msg(),
        'input' => $json
    ]);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

logError("Processing request", [
    'model' => $data['model'] ?? 'not set',
    'max_tokens' => $data['max_tokens'] ?? 'not set',
    'provider' => $data['provider'] ?? 'not set',
    'content_length' => strlen($data['content'] ?? '')
]);

try {
    $result = rewriteContent(
        $data['content'] ?? '',
        $data['model'] ?? 'gpt-4o',
        $data['max_tokens'] ?? 16384,
        $data['provider'] ?? 'openai'
    );

    echo json_encode(['content' => $result]);
} catch (Exception $e) {
    logError("Exception occurred", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}