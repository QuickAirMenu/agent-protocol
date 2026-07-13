<?php
// shared/includes/deepseek.php

class DeepSeekClient {
    private string $apiKey;
    private string $baseUrl;
    private string $defaultSystemPrompt;

    public function __construct() {
        $this->apiKey  = getenv('DEEPSEEK_API_KEY') ?: '';
        $this->baseUrl = rtrim(getenv('DEEPSEEK_API_URL') ?: 'https://api.deepseek.com', '/');
        $this->defaultSystemPrompt = getenv('ZOE_SYSTEM_PROMPT')
            ?: 'أنت Zoe، مساعد ذكي شخصي عربي. تجيب بالعربية بشكل واضح ومختصر وودود. تساعد في المهام اليومية، التذكيرات، والمعلومات العامة.';
    }

    public function chat(array $messages, int $maxTokens = 1000): ?string {
        if (empty($this->apiKey)) {
            zoeLog('error', 'DeepSeek API key not configured');
            return null;
        }

        $payload = [
            'model'       => 'deepseek-chat',
            'messages'    => $messages,
            'max_tokens'  => $maxTokens,
            'temperature' => 0.7,
        ];

        $ch = curl_init($this->baseUrl . '/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            zoeLog('error', "DeepSeek curl error: $curlError");
            return null;
        }
        if ($httpCode !== 200) {
            zoeLog('error', "DeepSeek HTTP $httpCode: $response");
            return null;
        }

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    public function simple(string $userMessage, ?string $systemPrompt = null): ?string {
        return $this->chat([
            ['role' => 'system', 'content' => $systemPrompt ?? $this->defaultSystemPrompt],
            ['role' => 'user',   'content' => $userMessage],
        ]);
    }

    public function withHistory(int $userId, string $newMessage, PDO $pdo, ?string $systemPrompt = null): ?string {
        $stmt = $pdo->prepare(
            "SELECT role, content FROM chat_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 20"
        );
        $stmt->execute([$userId]);
        $history = array_reverse($stmt->fetchAll());

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt ?? $this->defaultSystemPrompt],
        ];
        foreach ($history as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $newMessage];

        $response = $this->chat($messages, 2000);

        if ($response !== null) {
            $insert = $pdo->prepare(
                "INSERT INTO chat_history (user_id, role, content, source) VALUES (?, ?, ?, ?)"
            );
            $insert->execute([$userId, 'user', $newMessage, 'dashboard']);
            $insert->execute([$userId, 'assistant', $response, 'dashboard']);
        }

        return $response;
    }
}

function getDeepSeek(): DeepSeekClient {
    static $instance = null;
    if ($instance === null) $instance = new DeepSeekClient();
    return $instance;
}
