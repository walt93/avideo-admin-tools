<?php
// Disable output buffering
if (ob_get_level()) ob_end_clean();

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'AIModelRouter.php';

$router = new AIModelRouter(__DIR__);

// System prompt that was previously hardcoded
$systemPrompt = 'You are an expert encyclopedia editor. Your task is to rewrite content while maintaining complete factual accuracy. Do not add information, remove details, or make assumptions. Focus on improving clarity, structure, and academic tone while preserving the exact meaning and all specific details from the source material. The output should be at least as detailed as the input.';

// User prompt that was previously hardcoded
$userPrompt = "Rewrite the following text in an authoritative encyclopedia style. Your output MUST be equal to or longer than the input text - do not condense or summarize. Expand explanations where appropriate while maintaining absolute factual accuracy. Keep every single detail, data point, and nuance from the source. Structure the content with improved clarity and formal academic tone, but never sacrifice completeness for conciseness. You should elaborate on concepts where it adds clarity, use precise language, and maintain comprehensive coverage of all points in the original text.";

// Read the raw POST data
$json = file_get_contents('php://input');
$router->log("Received request", ['raw_input' => $json]);

// Decode JSON request
$data = json_decode($json, true);
if (!$data) {
    $router->log("JSON decode failed", [
        'error' => json_last_error_msg(),
        'input' => $json
    ]);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

try {
    // Set streaming headers
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // Disable nginx buffering

    // If using FastCGI, send initial response
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // The rewriteContent method will now handle streaming the response
    $router->rewriteContent(
        $data['content'] ?? '',
        $systemPrompt,
        $userPrompt,
        $data['model'] ?? 'gpt-4o',
        $data['max_tokens'] ?? 16384,
        $data['provider'] ?? 'openai'
    );

} catch (Exception $e) {
    $router->log("Exception occurred", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // Send error event
    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
}