<?php

class AIModelRouter {
    private $logger;
    private $apiKeys;
    private $providerConfigs;
    private $openAIModelConfigs;

    public function __construct($logDirectory = null) {
        $this->initializeLogger($logDirectory);
        $this->initializeAPIKeys();
        $this->initializeProviderConfigs();
        $this->initializeOpenAIModelConfigs();

        // Test logging
        $this->log("AIModelRouter initialized", [
            'time' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'sapi' => php_sapi_name()
        ]);
    }

    // Public method to access logging functionality
    public function log($message, $data = null) {
        $this->logger->logError($message, $data);
    }

    private function initializeLogger($logDirectory) {
        $this->logger = new class($logDirectory) {
            private $logDir;

            public function __construct($dir) {
                $this->logDir = $dir ?? __DIR__;
            }

            public function logError($message, $data = null) {
                $log_message = "[" . date('Y-m-d H:i:s') . "] Rewrite API Error: " . $message;
                if ($data) {
                    $log_message .= "\nData: " . print_r($data, true);
                }

                error_log($log_message);

                $specific_log = $this->logDir . '/rewrite_api.log';
                file_put_contents($specific_log, $log_message . "\n", FILE_APPEND);

                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
            }
        };
    }

    private function initializeAPIKeys() {
        $this->apiKeys = [
            'openai' => getenv('OPENAI_API_KEY'),
            'groq' => getenv('GROQ_API_KEY'),
            'anthropic' => getenv('ANTHROPIC_API_KEY')
        ];
    }

    private function initializeProviderConfigs() {
        $this->providerConfigs = [
            'openai' => [
                'base_url' => 'https://api.openai.com/v1/chat/completions',
                'auth_header' => 'Bearer'
            ],
            'groq' => [
                'base_url' => 'https://api.groq.com/openai/v1/chat/completions',
                'auth_header' => 'Bearer'
            ],
            'anthropic' => [
                'base_url' => 'https://api.anthropic.com/v1/messages',
                'auth_header' => 'x-api-key'
            ]
        ];
    }

    private function initializeOpenAIModelConfigs() {
        $this->openAIModelConfigs = [
            'gpt-4o' => [
                'supports_system_message' => true,
                'supports_temperature' => true,
                'token_param_name' => 'max_tokens'
            ],
            'gpt-4o-mini' => [
                'supports_system_message' => true,
                'supports_temperature' => true,
                'token_param_name' => 'max_tokens'
            ],
            'o1-mini' => [
                'supports_system_message' => false,
                'supports_temperature' => false,
                'token_param_name' => 'max_completion_tokens'
            ],
            'o1-preview' => [
                'supports_system_message' => false,
                'supports_temperature' => false,
                'token_param_name' => 'max_completion_tokens'
            ]
        ];
    }

    public function rewriteContent($content, $systemPrompt, $userPrompt, $model = 'gpt-4o', $max_tokens = 16384, $provider = 'openai') {
        $this->log("Starting rewrite request", [
            'content_length' => strlen($content),
            'model' => $model,
            'max_tokens' => $max_tokens,
            'provider' => $provider
        ]);

        try {
            $api_key = $this->getProviderApiKey($provider);

            switch ($provider) {
                case 'openai':
                    return $this->handleOpenAIRequest($content, $systemPrompt, $userPrompt, $model, $max_tokens, $api_key);
                case 'anthropic':
                    return $this->handleAnthropicRequest($content, $systemPrompt, $userPrompt, $model, $max_tokens, $api_key);
                case 'groq':
                    return $this->handleGroqRequest($content, $systemPrompt, $userPrompt, $model, $max_tokens, $api_key);
                default:
                    throw new Exception("Unsupported provider: " . $provider);
            }
        } catch (Exception $e) {
            $this->log("Exception in rewriteContent", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function getProviderApiKey($provider) {
        if (!isset($this->apiKeys[$provider])) {
            throw new Exception("Invalid provider");
        }

        if (empty($this->apiKeys[$provider])) {
            throw new Exception("API key not configured for " . $provider);
        }

        return $this->apiKeys[$provider];
    }

    private function calculateTokenLimit($content, $max_tokens) {
        $estimated_input_tokens = ceil((strlen($content) + 500) / 4);
        return min($estimated_input_tokens * 2 + 1000, intval($max_tokens));
    }

    private function handleOpenAIRequest($content, $systemPrompt, $userPrompt, $model, $max_tokens, $api_key) {
        $this->log("Handling OpenAI request", ['model' => $model]);

        if (!isset($this->openAIModelConfigs[$model])) {
            throw new Exception("Unsupported OpenAI model: " . $model);
        }

        $model_config = $this->openAIModelConfigs[$model];
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ];

        $messages = [];
        if ($model_config['supports_system_message']) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userPrompt . "\n\n" . $content
        ];

        $data = [
            'model' => $model,
            'messages' => $messages
        ];

        if ($model_config['supports_temperature']) {
            $data['temperature'] = 0.3;
        }

        $data[$model_config['token_param_name']] = $this->calculateTokenLimit($content, $max_tokens);

        return $this->makeAPIRequest(
            $this->providerConfigs['openai']['base_url'],
            $headers,
            $data,
            'choices.0.message.content'
        );
    }

    private function handleGroqRequest($content, $systemPrompt, $userPrompt, $model, $max_tokens, $api_key) {
        $this->log("Handling Groq request", ['model' => $model]);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ];

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userPrompt . "\n\n" . $content
            ]
        ];

        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => $this->calculateTokenLimit($content, $max_tokens)
        ];

        return $this->makeAPIRequest(
            $this->providerConfigs['groq']['base_url'],
            $headers,
            $data,
            'choices.0.message.content'
        );
    }

    private function handleAnthropicRequest($content, $systemPrompt, $userPrompt, $model, $max_tokens, $api_key) {
        $this->log("Handling Anthropic request", ['model' => $model]);

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01'
        ];

        $data = [
            'model' => $model,
            'max_tokens' => $this->calculateTokenLimit($content, $max_tokens),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $systemPrompt . "\n\n" . $userPrompt . "\n\n" . $content
                ]
            ]
        ];

        return $this->makeAPIRequest(
            $this->providerConfigs['anthropic']['base_url'],
            $headers,
            $data,
            'content.0.text'
        );
    }

    private function makeAPIRequest($url, $headers, $data, $responseKey) {
        $this->log("Making API request", [
            'url' => $url,
            'headers' => array_map(function($header) {
                return preg_replace('/Bearer \S+|x-api-key: \S+/', '[REDACTED]', $header);
            }, $headers)
        ]);

        // Add streaming parameter to request data
        $data['stream'] = true;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 0, // Remove timeout limit
            CURLOPT_WRITEFUNCTION => function($ch, $data) use ($responseKey) {
                $this->processStreamChunk($data, $responseKey);
                echo $data; // Send chunk to client immediately
                flush(); // Ensure output is sent
                return strlen($data);
            }
        ]);

        // Start output buffering and send headers
        ob_start();
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->log("CURL Error", ['error' => $error]);
            throw new Exception("API request failed: " . $error);
        }

        curl_close($ch);
        return ""; // Empty return since we've already streamed the response
    }

    private function processStreamChunk($chunk, $responseKey) {
        // Different handling based on provider
        if (strpos($responseKey, 'choices.0.message.content') !== false) {
            // OpenAI format
            $lines = explode("\n", $chunk);
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                if ($line === "data: [DONE]") continue;

                $json = json_decode(substr($line, 6), true); // Remove "data: " prefix
                if ($json && isset($json['choices'][0]['delta']['content'])) {
                    $text = $json['choices'][0]['delta']['content'];
                    echo "data: " . json_encode(['content' => $text]) . "\n\n";
                }
            }
        } elseif (strpos($responseKey, 'content.0.text') !== false) {
            // Anthropic format
            $json = json_decode($chunk, true);
            if ($json && isset($json['delta']['text'])) {
                echo "data: " . json_encode(['content' => $json['delta']['text']]) . "\n\n";
            }
        }
    }

// Update the frontend JavaScript to handle streaming
    public function getStreamingJS() {
        return "
    async function streamContent(endpoint, data) {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let content = '';

            while (true) {
                const {value, done} = await reader.read();
                if (done) break;
                
                const chunk = decoder.decode(value);
                const lines = chunk.split('\\n');
                
                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        try {
                            const data = JSON.parse(line.slice(6));
                            content += data.content;
                            // Update UI with accumulated content
                            document.getElementById('aiRewrite').value = content;
                            updateStats(content, 'aiWordCount', 'aiCharCount');
                        } catch (e) {
                            console.error('Error parsing chunk:', e);
                        }
                    }
                }
            }
            
            return content;
        } catch (error) {
            throw new Error('Streaming failed: ' + error.message);
        }
    }";
}