<?php
// api/analyze_sentiment.php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Define the base path
define('BASE_PATH', realpath(__DIR__ . '/..'));

// Include files using absolute paths
require_once BASE_PATH . '/database.php';
require_once BASE_PATH . '/config.php';
require_once BASE_PATH . '/models/Entry.php';
require_once BASE_PATH . '/models/SentimentAnalysis.php';
require_once __DIR__ . '/AIModelRouter.php';

// Debug log the paths
error_log("Base path: " . BASE_PATH);
error_log("Database path: " . BASE_PATH . '/database.php');

$router = new AIModelRouter(__DIR__);
$entry = new Entry();

// Log the request
$json = file_get_contents('php://input');
$router->log("Received request", ['raw_input' => $json]);

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
    // Log the content we're about to analyze
    $router->log("Content to analyze", [
        'content_length' => strlen($data['content'] ?? ''),
        'entry_id' => $data['entry_id'] ?? null
    ]);

    // Get the sentiment analysis prompt with examples
    $prompt = "Analyze the following content for sentiment and identify any strong positions or biases present. For each significant topic or position identified, create an appropriate XML tag with a score from -10 (strongly against) to +10 (strongly for). Always include an overall sentiment score.

Required format:
<sentiment>value between -10 and +10</sentiment>
<topic_name>score</topic_name>

Example response:
<sentiment>7</sentiment>
<privacy>9</privacy>
<surveillance>-8</surveillance>
<technology>5</technology>

Analyze the following content:

" . ($data['content'] ?? '');

    // Get the analysis from the AI model
    $result = $router->rewriteContent(
        $data['content'] ?? '',
        "You are a sentiment analysis expert. Your task is to analyze content and identify key topics and sentiments.",
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

    // Log the parsed data
    $router->log("Parsed sentiment data", [
        'json_data' => $jsonData
    ]);

    // If an entry ID was provided, save the sentiment analysis
    if (isset($data['entry_id'])) {
        $entry->saveSentimentAnalysis($data['entry_id'], $jsonData);
    }

    // Create an analyzer instance with the parsed data
    $visualizer = new SentimentAnalysis(json_decode($jsonData, true));

    // Return both the raw data and rendered HTML
    echo json_encode([
        'raw_xml' => $result,
        'sentiment_data' => json_decode($jsonData),
        'html' => $visualizer->render()
    ]);

} catch (Exception $e) {
    $router->log("Exception occurred", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}