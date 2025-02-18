<?php
function rewriteContent($content, $model = 'gpt-4o', $max_tokens = 16384, $provider = 'openai') {
    // Validate and get API key
    $api_key = getProviderApiKey($provider);

    // Get provider-specific configuration
    $config = getProviderConfig($provider, $model);

    // Calculate token limit
    $token_limit = calculateTokenLimit($content, $max_tokens);

    try {
        // Dispatch to provider-specific handler
        switch ($provider) {
            case 'openai':
                return handleOpenAIRequest($content, $model, $token_limit, $api_key, $config);
            case 'anthropic':
                return handleAnthropicRequest($content, $model, $token_limit, $api_key, $config);
            case 'groq':
                return handleGroqRequest($content, $model, $token_limit, $api_key, $config);
            default:
                throw new Exception("Unsupported provider: " . $provider);
        }
    } catch (Exception $e) {
        logError("Exception occurred", [
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

function handleGroqRequest($content, $model, $token_limit, $api_key, $config) {
    // Prepare headers
    $headers = [
        'Content-Type: application/json',
        "{$config['auth_header']}: {$api_key}"
    ];

    // Prepare messages
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

    // Prepare request data
    $data = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.3,
        'max_tokens' => $token_limit
    ];

    logError("Groq Request Configuration", [
        'url' => $config['base_url'],
        'model' => $model,
        'token_limit' => $token_limit,
        'data' => $data
    ]);

    // Make request
    $response = makeApiRequest($config['base_url'], $headers, $data);

    // Parse Groq response
    if (!isset($response['choices'][0]['message']['content'])) {
        logError("Invalid Groq response structure", ['response' => $response]);
        throw new Exception("Invalid response structure from Groq API");
    }

    return $response['choices'][0]['message']['content'];
}

function handleAnthropicRequest($content, $model, $token_limit, $api_key, $config) {
    // Prepare headers
    $headers = [
        'Content-Type: application/json',
        "{$config['auth_header']}: {$api_key}",
        'anthropic-version: 2023-06-01'
    ];

    // Prepare messages (Anthropic format)
    $data = [
        'model' => $model,
        'max_tokens' => $token_limit,
        'messages' => [
            [
                'role' => 'user',
                'content' => "You are an expert encyclopedia editor. Rewrite the following content while maintaining complete factual accuracy. Do not add information, remove details, or make assumptions. Focus on improving clarity, structure, and academic tone while preserving the exact meaning and all specific details.\n\n" . $content
            ]
        ]
    ];

    logError("Anthropic Request Configuration", [
        'url' => $config['base_url'],
        'model' => $model,
        'token_limit' => $token_limit,
        'data' => $data
    ]);

    // Make request
    $response = makeApiRequest($config['base_url'], $headers, $data);

    // Parse Anthropic response
    if (!isset($response['content'][0]['text'])) {
        logError("Invalid Anthropic response structure", ['response' => $response]);
        throw new Exception("Invalid response structure from Anthropic API");
    }

    return $response['content'][0]['text'];
}

function handleOpenAIRequest($content, $model, $token_limit, $api_key, $config) {
    // Prepare headers
    $headers = [
        'Content-Type: application/json',
        "{$config['auth_header']}: {$api_key}"
    ];

    // Prepare messages
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

    // Prepare request data
    $data = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.3,
        'max_tokens' => $token_limit
    ];

    logError("OpenAI Request Configuration", [
        'url' => $config['base_url'],
        'model' => $model,
        'token_limit' => $token_limit,
        'data' => $data
    ]);

    // Make request
    $response = makeApiRequest($config['base_url'], $headers, $data);

    // Parse OpenAI response
    if (!isset($response['choices'][0]['message']['content'])) {
        logError("Invalid OpenAI response structure", ['response' => $response]);
        throw new Exception("Invalid response structure from OpenAI API");
    }

    return $response['choices'][0]['message']['content'];
}

function makeApiRequest($url, $headers, $data) {
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

    // Parse response
    $result = json_decode($response, true);
    if (!$result) {
        logError("Invalid JSON response", ['response' => $response]);
        throw new Exception("Invalid API response format");
    }

    return $result;
}