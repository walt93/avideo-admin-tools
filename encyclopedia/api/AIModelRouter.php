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
        $this->logger->logError("AIModelRouter initialized", [
            'time' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'sapi' => php_sapi_name()
        ]);
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
        $this->logger->logError("Starting rewrite request", [
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
            $this->logger->logError("Exception in rewriteContent", [
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
        $this->logger->logError("Handling OpenAI request", ['model' => $model]);

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
        $this->logger->logError("Handling Groq request", ['model' => $model]);

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
        $this->logger->logError("Handling Anthropic request", ['model' => $model]);

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
        $this->logger->logError("Making API request", [
            'url' => $url,
            'headers' => array_map(function($header) {
                return preg_replace('/Bearer \S+|x-api-key: \S+/', '[REDACTED]', $header);
            }, $headers)
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->logger->logError("API response received", [
            'status' => $http_status,
            'response_length' => strlen($response)
        ]);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->logger->logError("CURL Error", ['error' => $error]);
            throw new Exception("API request failed: " . $error);
        }

        curl_close($ch);

        if ($http_status !== 200) {
            $this->logger->logError("API Error Response", [
                'status' => $http_status,
                'response' => $response
            ]);

            $error_message = "API request failed";
            if ($response) {
                $error_data = json_decode($response, true);
                if ($error_data && isset($error_data['error']['message'])) {
                    $error_message .= ": " . $error_data['error']['message'];
                }
            }
            throw new Exception($error_message);
        }

        $result = json_decode($response, true);
        if (!$result) {
            $this->logger->logError("JSON decode failed", [
                'json_error' => json_last_error_msg(),
                'response' => $response
            ]);
            throw new Exception("Invalid API response format");
        }

        // Parse response path
        $keys = explode('.', $responseKey);
        $value = $result;
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                $this->logger->logError("Invalid response structure", ['response' => $result]);
                throw new Exception("Invalid API response structure");
            }
            $value = $value[$key];
        }

        return $value;
    }
}