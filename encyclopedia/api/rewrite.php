<?php
header('Content-Type: application/json');

function rewriteContent($content) {
    $api_key = getenv('OPENAI-API-KEY');

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
        'temperature' => 0.3  // Lower temperature for more consistent output
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status !== 200) {
        http_response_code(500);
        echo json_encode(['error' => 'API request failed']);
        exit;
    }

    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'];
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No content provided']);
        exit;
    }

    try {
        $rewritten = rewriteContent($input['content']);
        echo json_encode(['content' => $rewritten]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}