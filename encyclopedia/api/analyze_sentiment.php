<?php
// api/analyze_sentiment.php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once '../models/Entry.php';
require_once '../AIModelRouter.php';

$router = new AIModelRouter(__DIR__);
$entry = new Entry();

// Read the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

try {
    // Get the sentiment analysis prompt
    $prompt = "Analyze the following content for sentiment and identify any strong positions or biases present. For each significant topic or position identified, create an appropriate XML tag with a score from -10 (strongly against) to +10 (strongly for). Always include an overall sentiment score. Required format: <sentiment>value between -10 and +10</sentiment> <topic_name>score</topic_name>";

    // If we have previous sentiment data, include the topics
    $previousSentiment = null;
    if (isset($data['entry_id'])) {
        $previousSentiment = $entry->getSentimentAnalysis($data['entry_id']);
        if ($previousSentiment) {
            $topics = [];
            foreach (json_decode($previousSentiment, true) as $topic => $score) {
                if ($topic !== 'sentiment') {
                    $topics[] = $topic;
                }
            }
            if (!empty($topics)) {
                $prompt .= "\n\nPlease analyze and score the following specific topics: " . implode(', ', $topics);
            }
        }
    }

    // Get the analysis from the AI model
    $result = $router->rewriteContent(
        $data['content'] ?? '',
        "You are a sentiment analysis expert. Your task is to analyze content and identify key topics and sentiments.",
        $prompt,
        $data['model'] ?? 'gpt-4o',
        $data['max_tokens'] ?? 16384,
        $data['provider'] ?? 'openai'
    );

    // Create a new SentimentAnalysis instance
    require_once '../SentimentAnalysis.php';
    $analyzer = new SentimentAnalysis();

    // Parse the XML and convert to JSON
    $jsonData = $analyzer->parseFromXML($result);

    // If an entry ID was provided, save the sentiment analysis
    if (isset($data['entry_id'])) {
        $entry->saveSentimentAnalysis($data['entry_id'], $jsonData);
    }

    // Return both the raw XML and parsed JSON
    echo json_encode([
        'raw_xml' => $result,
        'sentiment_data' => $jsonData
    ]);

} catch (Exception $e) {
    $router->log("Exception occurred", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}