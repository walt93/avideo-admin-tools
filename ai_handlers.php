<?php
// ai_handlers.php

function get_speaker_prompt($speakerName) {
    return <<<EOT
You are writing for Truth Tide TV, describing a video featuring {$speakerName}. Focus on:
1. Establishing {$speakerName}'s credibility and expertise in the first few words
2. Use active voice with {$speakerName} as the subject
3. Begin with "{$speakerName}" to immediately identify the speaker
4. Describe what they're sharing/revealing/discussing rather than the video format
5. Keep the format: "[Name] [credentials/role] [action verb] [subject matter]"
6. Include their strongest credential if known from the content

Guidelines:
- Avoid passive constructions like "In this video..."
- Focus on what the speaker is communicating
- Use authoritative but measured tone
- Keep to 300 characters maximum
- Follow all standard content guidelines

Write a single compelling speaker-focused description that fits within 300 characters.
EOT;
}

function get_base_prompt() {
    return <<<EOT
You are writing for Truth Tide TV, a Roku channel dedicated to exposing suppressed truths, hidden knowledge, and crimes against humanity that mainstream media won't cover.

Guidelines:
1. Use clear, precise language without sensationalism or hyperbole
2. Focus on facts and verifiable information from the provided content only
3. Maintain an objective tone, avoiding editorializing
4. Use active voice and strong verbs, but avoid clichés
5. Prioritize clarity and brevity over dramatic language
6. Include relevant context when necessary
7. Avoid loaded terms or phrases that might indicate bias
8. Do not use words from the Forbidden Words List
9. If specific details (names, places, dates) are not available, focus on the main message or theme of the content

When specific details are not available:
- Describe the nature of the content (e.g., "An anonymous whistleblower's account")
- Focus on the main topic or issue discussed
- Mention the format of the content (e.g., "In this detailed monologue")
- Highlight any key arguments or points made
- If applicable, mention the potential impact or significance of the information

CRITICAL: Always include specific details such as names, places, and dates when they are central to the content. These details should be prominently featured in the description.

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

FORBIDDEN WORDS LIST: Unveil, Delve, Tapestry, Reveal, Uncover, Expose, Shocking,
Stunning, Mind-blowing, Game-changing, Paradigm-shifting, Revolutionary,
Earth-shattering, Unbelievable, Jaw-dropping, Dive into, Explore, Journey,
Unveiling, Unearthing, Peeling back the layers, Unlocking the secrets,
Shedding light on, Lifting the veil, Explosive, Bombshell, Deep dive,
Eye-opening, Groundbreaking

Write a single compelling TV description. STRICT LIMIT: 300 characters maximum (including spaces).
Focus on answering "Why must I watch this?"
The text must fit in 300 characters.
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
    $start_sample = implode(' ', array_slice($words, 0, 1000));
    $end_sample = implode(' ', array_slice($words, -500));
    $content_sample = $start_sample . "\n[...]\n" . $end_sample;

    $prompt = get_base_prompt() . "\n\nSample from content to categorize and describe:\n{$content_sample}";
    
    // Create proper data structure for OpenAI API
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
        'max_tokens' => 200
    ];
    
    return call_openai_api($data);
}

function rewrite_existing($title, $description) {
    $prompt = get_base_prompt() . "\n\nRewrite this content in our voice:\nTitle: {$title}\nDescription: {$description}";
    
    // Create proper data structure for OpenAI API
    $data = [
        'model' => 'gpt-4-0125-preview',
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
    
    return call_openai_api($data);
}

function generate_event_style($title, $description) {
    $prompt = get_base_prompt() . "\n\n" .
        "Extract any dates and locations from this content and incorporate them into a compelling event-focused description:\n" .
        "Title: {$title}\nDescription: {$description}\n\n" .
        "If no specific date/location is found, focus on the event's significance while maintaining our voice.";
    
    // Create proper data structure for OpenAI API
    $data = [
        'model' => 'gpt-4-0125-preview',
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
    
    return call_openai_api($data);
}

function call_openai_api($data) {
    $openaiKey = getenv('OPENAI_API_KEY');
    if (!$openaiKey) {
        error_log("OpenAI API key not found");
        throw new Exception('OpenAI API key not configured');
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    
    $jsonData = json_encode($data);
    error_log("Sending request to OpenAI API with data: " . $jsonData);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $openaiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => $jsonData
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        error_log("Curl error: " . curl_error($ch));
        curl_close($ch);
        throw new Exception('Curl error: ' . curl_error($ch));
    }
    
    error_log("OpenAI API response code: " . $httpCode);
    error_log("OpenAI API response: " . $response);
    
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("OpenAI API request failed with status " . $httpCode . ": " . $response);
        throw new Exception('OpenAI API request failed');
    }

    $result = json_decode($response, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        error_log("Invalid response structure from OpenAI: " . $response);
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

            case 'speaker':
                if (!isset($post['title']) || !isset($post['description']) || !isset($post['speaker'])) {
                    throw new Exception('Title, description and speaker name required');
                }
                $description = generate_speaker_style($post['title'], $post['description'], $post['speaker']);
                break;

            default:
                throw new Exception('Invalid generation type');
        }

        echo json_encode(['success' => true, 'description' => $description]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function generate_speaker_style($title, $description, $speakerName) {
    $prompt = get_speaker_prompt($speakerName) . "\n\nTitle: {$title}\nDescription: {$description}";

    $data = [
        'model' => 'gpt-4-0125-preview',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are Truth Tide TV\'s lead editor, specializing in speaker-focused content that emphasizes authority and expertise.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.3,
        'max_tokens' => 200
    ];

    return call_openai_api($data);
}
