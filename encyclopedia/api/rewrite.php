<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'AIModelRouter.php';

$router = new AIModelRouter(__DIR__);

// System prompt
$systemPrompt = 'You are an expert encyclopedia editor. Your task is to rewrite content while maintaining complete factual accuracy. Do not add information, remove details, or make assumptions. Focus on improving clarity, structure, and academic tone while preserving the exact meaning and all specific details from the source material. The output should be at least as detailed as the input.';

// User prompt
$userPrompt = "Rewrite the following text in an authoritative encyclopedia style. Your output MUST be equal to or longer than the input text - do not condense or summarize. Expand explanations where appropriate while maintaining absolute factual accuracy. Keep every single detail, data point, and nuance from the source. Structure the content with improved clarity and formal academic tone, but never sacrifice completeness for conciseness. You should elaborate on concepts where it adds clarity, use precise language, and maintain comprehensive coverage of all points in the original text.";

// Read the raw POST data
$json = file_get_contents('php://input');
$router->logger->logError("Received request", ['raw_input' => $json]);

// Decode JSON request
$data = json_decode($json, true);
if (!$data) {
    $router->logger->logError("JSON decode failed", [
        'error' => json_last_error_msg(),
        'input' => $json
    ]);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

try {
    $result = $router->rewriteContent(
        $data['content'] ?? '',
        $systemPrompt,
        $userPrompt,
        $data['model'] ?? 'gpt-4o',
        $data['max_tokens'] ?? 16384,
        $data['provider'] ?? 'openai'
    );

    echo json_encode(['content' => $result]);
} catch (Exception $e) {
    $router->logger->logError("Exception occurred", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}