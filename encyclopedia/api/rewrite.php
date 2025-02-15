<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set up error logging
function logError($message) {
    error_log("Rewrite API Error: " . $message);
}

header('Content-Type: application/json');

function rewriteContent($content) {
    $api_key = getenv('OPENAI-API-KEY');

    if (!$api_key) {
        logError("OpenAI API key not found in environment");
        throw new Exception("API key not configured");
    }

    $prompt = "Rewrite the following text in an authoritative encyclopedia style. Maintain absolute fidelity to the source material - do not add, remove, or modify any factual claims. Focus on clarity, precision, and academic tone while preserving all original information and context. Here is the content to rewrite:\n\n";

    $data = [
        'model' => 'gpt-4',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are an expert encyclopedia editor. Your task is to rewrite content while maintaining complete factual accuracy. Do not add information, remove details, or make assumptions. Focus on improving clarity, structure, and academic tone while preserving the exact meaning and all specific details from the source material.'
            ],
            [
                'role' => 'user',
                'content' => $prompt . $content
            ]
        ],
        'temperature' => 0.3
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

    // Log CURL errors if any
    if (curl_errno($ch)) {
        logError("CURL Error: " . curl_error($ch));
        throw new Exception("CURL Error: " . curl_error($ch));
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
        throw new Exception($error_message);
    }

    $result = json_decode($response, true);
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        logError("Invalid API response structure: " . print_r($result, true));
        throw new Exception("Invalid API response");
    }

    return $result['choices'][0]['message']['content'];
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = file_get_contents('php://input');
        logError("Received input: " . $input);

        $decoded = json_decode($input, true);
        if (!$decoded || !isset($decoded['content'])) {
            throw new Exception('No content provided or invalid JSON');
        }

        $rewritten = rewriteContent($decoded['content']);
        echo json_encode(['content' => $rewritten]);

    } catch (Exception $e) {
        logError("Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'details' => 'Check error log for more information'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}