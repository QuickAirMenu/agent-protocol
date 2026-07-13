<?php
// zoe-app/includes/telegram.php

class TelegramBot {
    private string $token;
    private string $apiBase;

    public function __construct() {
        $this->token   = getenv('TELEGRAM_BOT_TOKEN') ?: '';
        $this->apiBase = "https://api.telegram.org/bot{$this->token}";
    }

    private function request(string $method, array $data = []): ?array {
        $ch = curl_init("{$this->apiBase}/{$method}");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            zoeLog('error', "TG curl error: $err");
            return null;
        }
        return json_decode($resp, true);
    }

    public function sendMessage(int|string $chatId, string $text, array $extra = []): ?array {
        return $this->request('sendMessage', array_merge([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ], $extra));
    }

    public function sendKeyboard(int|string $chatId, string $text, array $buttons): ?array {
        return $this->sendMessage($chatId, $text, [
            'reply_markup' => ['inline_keyboard' => $buttons],
        ]);
    }

    public function editMessage(int|string $chatId, int $messageId, string $text, array $extra = []): ?array {
        return $this->request('editMessageText', array_merge([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ], $extra));
    }

    public function answerCallback(string $callbackId, string $text = ''): ?array {
        return $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text'              => $text,
        ]);
    }

    public function sendTyping(int|string $chatId): void {
        $this->request('sendChatAction', ['chat_id' => $chatId, 'action' => 'typing']);
    }
}

function getTelegram(): TelegramBot {
    static $instance = null;
    if ($instance === null) $instance = new TelegramBot();
    return $instance;
}
