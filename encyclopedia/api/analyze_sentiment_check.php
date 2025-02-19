<?php
// api/analyze_sentiment_check.php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../models/SentimentAnalysis.php';
require_once __DIR__ . '/AIModelRouter.php';

$router = new AIModelRouter(__DIR__);

// Read the raw POST data
$json = file_get_contents('php://input');
$router->log("Received request", ['raw_input' => $json]);

$data = json_decode($json, true);
if (!$data || !isset($data['content']) || !isset($data['topics'])) {
    $router->log("Invalid request data", [
        'error' => 'Missing required fields',
        'data' => $data
    ]);
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Create prompt with specific topics
    $topics = implode(', ', $data['topics']);
    $prompt = "Analyze the following content and provide sentiment scores specifically for these topics: {$topics}. Score each topic from -10 (strongly against) to +10 (strongly for). Also provide an overall sentiment score.

Required format:
<sentiment>value between -10 and +10</sentiment>
For each topic:
<topic_name>score</topic_name>

For example, if analyzing 'privacy' and 'technology':
<sentiment>-5</sentiment>
<privacy>-8</privacy>
<technology>3</technology>

Analyze ONLY these specific topics: {$topics}
";

    // Get the analysis from the AI model
    $result = $router->rewriteContent(
        $data['content'] ?? '',
        "You are a sentiment analysis expert focusing on specific topics.",
        $prompt,
        $data['model'] ?? 'gpt-4o',
        $data['max_tokens'] ?? 16384,
        $data['provider'] ?? 'openai'
    );

    $router->log("Received AI response", [
        'response' => $result,
        'length' => strlen($result)
    ]);

    // Create a new SentimentAnalysis instance
    $analyzer = new SentimentAnalysis();

    // Parse the XML and convert to JSON
    $jsonData = $analyzer->parseFromXML($result);

    // Return the results
    echo json_encode([
        'raw_xml' => $result,
        'sentiment_data' => $jsonData,
        'html' => $analyzer->render()
    ]);

} catch (Exception $e) {
    $router->log("Exception occurred", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}