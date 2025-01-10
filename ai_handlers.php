<?php
// ai_handlers.php

function get_base_prompt() {
    return <<<EOT
You are writing for Truth Tide TV, a channel dedicated to exposing suppressed truths, hidden knowledge, and crimes against humanity that mainstream media won't cover.

FIRST: Identify which core domain this content belongs to and use the appropriate stance:

MEDICAL/HEALTH:
- Expose dangers of experimental medical technologies
- Reveal corporate/government medical coercion
- Document real-world harm from medical experiments
- Emphasize bodily autonomy and informed consent

ANCIENT KNOWLEDGE/COSMIC:
- Reveal suppressed historical truths
- Challenge false mainstream historical narratives
- Connect ancient wisdom to present circumstances
- Acknowledge catastrophic cosmic cycles

GOVERNMENT/ELITE CONTROL:
- Expose corruption and abuse of power
- Reveal hidden control mechanisms
- Document systematic rights violations
- Connect individual events to broader control patterns

CRIMES AGAINST HUMANITY:
- Expose systematic evil and ritual abuse
- Document trafficking and exploitation
- Reveal institutional complicity
- Connect to broader patterns of evil

UFO/DISCLOSURE/STRANGE PHENOMENON:
- Document increased sightings and patterns
- Connect to geopolitical developments
- Distinguish between military tech and unexplained
- Question official narratives and timing
- Consider multiple agendas (military, intelligence, breakaway)
- Note historical parallels in disclosure patterns
- Maintain skepticism of both denial AND engineered disclosure
- Stay grounded in documented evidence
- Consider false flag/psychological operation angles

Write a single compelling TV description. STRICT LIMIT: 250 characters maximum (including spaces).
Focus on answering "Why must I watch this?"
The text must fit in this space: [----------------------|] (250 chars)
EOT;
}

function generate_from_transcript($filename) {
    $transcript_path = "/var/www/html/conspyre.tv/videos/{$filename}/{$filename}.txt";
    
    if (!file_exists($transcript_path)) {
        throw new Exception('Transcript file not found');
    }

    $transcript = file_get_contents($transcript_path);
    if ($transcript === false) {
        throw new Exception('Failed to read transcript file');
    }

    // Sample strategy: Take first ~500 words and last ~200 words
    $words = str_word_count($transcript, 1);
    $start_sample = implode(' ', array_slice($words, 0, 500));
    $end_sample = implode(' ', array_slice($words, -200));
    $content_sample = $start_sample . "\n[...]\n" . $end_sample;

    $prompt = get_base_prompt() . "\n\nSample from content to categorize and describe:\n{$content_sample}";
    
    return call_openai_api($prompt);
}

function rewrite_existing($title, $description) {
    $prompt = get_base_prompt() . "\n\nRewrite this content in our voice:\nTitle: {$title}\nDescription: {$description}";
    
    return call_openai_api($prompt);
}

function generate_event_style($title, $description) {
    $prompt = get_base_prompt() . "\n\n" .
        "Extract any dates and locations from this content and incorporate them into a compelling event-focused description:\n" .
        "Title: {$title}\nDescription: {$description}\n\n" .
        "If no specific date/location is found, focus on the event's significance while maintaining our voice.";
    
    return call_openai_api($prompt);
}

function call_openai_api($prompt) {
    $openaiKey = getenv('OPENAI_API_KEY');
    if (!$openaiKey) {
        throw new Exception('OpenAI API key not configured');
    }

    $data = [
        'model' => 'gpt-4o',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are Truth Tide TV\'s lead editor. You write hard-hitting descriptions that expose concerning developments and wake people up to hidden truths.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.3,
        'max_tokens' => 100
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $openaiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('OpenAI API request failed');
    }

    $result = json_decode($response, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('Invalid response from OpenAI API');
    }

    return $result['choices'][0]['message']['content'];
}

function handleSanitize($post) {
    if (!isset($post['description'])) {
        echo json_encode(['success' => false, 'error' => 'Description required']);
        return;
    }

    try {
        $openaiKey = getenv('OPENAI_API_KEY');
        if (!$openaiKey) {
            echo json_encode(['success' => false, 'error' => 'OpenAI API key not configured']);
            return;
        }

        $data = [
            'model' => 'gpt-4-0125-preview',
            'messages' => [
            [
                'role' => 'system',
            'content' => 'You are a helpful assistant that sanitizes and formats video descriptions.'
    ],
        [
            'role' => 'user',
            'content' => <<<EOT
            You must preserve the exact meaning and information from the original text. Your only tasks are:
            1. Fix basic capitalization (beginning of sentences)
            2. Add correct punctuation if missing
                3. Remove emojis
            4. Remove email addresses and URLs
            5. Split into paragraphs where appropriate

            Rules:
            - DO NOT rewrite or paraphrase any content
            - DO NOT add or remove information
            - DO NOT change word choice
            - DO NOT remove hashtags
            - DO NOT "improve" the text
            - Keep line breaks where they exist in the original
            - Preserve ALL original terminology and phrasing
            - If something looks like a formatting error but you're not sure, leave it as is

            Original text:
            {$post['description']}

            Return ONLY the formatted text with no explanations.
            EOT
        ]
    ],
        'temperature' => 0.3
    ];

        $result = call_openai_api($data);
        echo json_encode(['success' => true, 'sanitized' => $result]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleDescriptionGeneration($post) {
    try {
        $description = '';

        switch ($post['type']) {
            case 'transcript':
                if (!isset($post['filename'])) {
                    throw new Exception('Filename not provided');
                }
                $description = generate_from_transcript($post['filename']);
                break;

            case 'rewrite':
                if (!isset($post['title']) || !isset($post['description'])) {
                    throw new Exception('Title and description required');
                }
                $description = rewrite_existing($post['title'], $post['description']);
                break;

            case 'event':
                if (!isset($post['title']) || !isset($post['description'])) {
                    throw new Exception('Title and description required');
                }
                $description = generate_event_style($post['title'], $post['description']);
                break;

            default:
                throw new Exception('Invalid generation type');
        }

        echo json_encode(['success' => true, 'description' => $description]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}