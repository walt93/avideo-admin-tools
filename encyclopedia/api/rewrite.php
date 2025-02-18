<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set up error logging
function logError($message, $data = null) {
    $log_message = "Rewrite API Error: " . $message;
    if ($data) {
        $log_message .= "\nData: " . print_r($data, true);
    }
    error_log($log_message);
}

header('Content-Type: application/json');

function rewriteContent($content, $model = 'gpt-4o', $max_tokens = 16384, $provider = 'openai') {
    // Get API keys from environment
    $openai_key = getenv('OPENAI_API_KEY');
    $groq_key = getenv('GROQ_API_KEY');

    // Determine if this is a Groq model
    $is_groq = isset($_POST['provider']) && $_POST['provider'] === 'groq';

    $api_key = $is_groq ? $groq_key : $openai_key;
    $base_url = $is_groq ? 'https://api.groq.com/openai/v1/chat/completions' : 'https://api.openai.com/v1/chat/completions';

    if (!$api_key) {
        logError(($is_groq ? "Groq" : "OpenAI") . " API key not found in environment");
        throw new Exception("API key not configured");
    }

    // Model-specific configurations
    $model_configs = [
        'o1-mini' => [
            'supports_system_message' => false
        ],
        // Add other models as needed
        'default' => [
            'supports_system_message' => true
        ]
    ];
    $model_config = $model_configs[$model] ?? $model_configs['default'];

    // Log the API request configuration
    logError("API Request Configuration", [
        'provider' => $provider,
        'model' => $model,
        'max_tokens' => $max_tokens,
        'base_url' => $base_url,
        'content_length' => strlen($content)
    ]);

    $system_content = 'You are an expert encyclopedia editor. Your task is to rewrite content while maintaining complete factual accuracy. Do not add information, remove details, or make assumptions. Focus on improving clarity, structure, and academic tone while preserving the exact meaning and all specific details from the source material. The output should be at least as detailed as the input.';

    $prompt = "Rewrite the following text in an authoritative encyclopedia style. Maintain absolute fidelity to the source material - do not add, remove, or modify any factual claims. Focus on clarity, precision, and academic tone while preserving all original information and context. The output length should match or exceed the input length while maintaining all details. Here is the content to rewrite:\n\n";

    // Calculate approximate input tokens (rough estimate: 4 chars per token)
    $estimated_input_tokens = ceil((strlen($prompt) + strlen($content)) / 4);

    // Set max_tokens to be 2x the input length to ensure we get full output
    // Add buffer for system message tokens
    $max_tokens = $estimated_input_tokens * 2 + 1000;

    // Cap at the model's maximum output length
    $max_tokens = min($max_tokens, intval($max_tokens));

    // Prepare messages based on model configuration
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
        // For models that don't support system messages, combine system content with user prompt
        $messages[] = [
            'role' => 'user',
            'content' => $system_content . "\n\n" . $prompt . $content
        ];
    }

    $data = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.3,
        'max_tokens' => $max_tokens
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);

    // Add these for debugging
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Get CURL errors if any
    if (curl_errno($ch)) {
        $curl_error = curl_error($ch);
        logError("CURL Error", [
            'error' => $curl_error,
            'errno' => curl_errno($ch)
        ]);
        throw new Exception("CURL Error: " . $curl_error);
    }

    // Log verbose output
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    logError("CURL Verbose Log: " . $verboseLog);

    // Log response for debugging
    logError("API Response: " . $response);
    logError("HTTP Status: " . $http_status);

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
        logError("API Error", [
            'status' => $http_status,
            'message' => $error_message,
            'response' => $response
        ]);
        throw new Exception($error_message);
    }

    $result = json_decode($response, true);
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        logError("Invalid API response structure: " . print_r($result, true));
        throw new Exception("Invalid API response");
    }

    // Compare input and output lengths for debugging
    $input_length = strlen($content);
    $output_length = strlen($result['choices'][0]['message']['content']);
    logError("Input length: " . $input_length . " chars");
    logError("Output length: " . $output_length . " chars");

    return $result['choices'][0]['message']['content'];
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