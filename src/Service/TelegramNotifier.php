<?php

namespace App\Service;

use App\Dto\ZabbixAlert;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TelegramNotifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $botToken,
        private readonly int $chatId,
        private readonly string $proxy = '',
    ) {
    }

    public function send(ZabbixAlert $alert): void
    {
        if ($this->botToken === '') {
            throw new \RuntimeException('TELEGRAM_BOT_TOKEN is empty');
        }

        $options = [
            'json' => [
                'chat_id' => $this->chatId,
                'text' => $alert->subject."\n\n".$alert->message,
            ],
            'timeout' => 15,
        ];

        if ($this->proxy !== '') {
            $options['proxy'] = $this->proxy;
        }

        $response = $this->httpClient->request(
            'POST',
            'https://api.telegram.org/bot'.$this->botToken.'/sendMessage',
            $options,
        );

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Telegram HTTP '.$status.': '.$response->getContent(false));
        }

        /** @var array{ok?:bool,description?:string} $body */
        $body = $response->toArray(false);
        if (($body['ok'] ?? false) !== true) {
            throw new \RuntimeException('Telegram API: '.($body['description'] ?? 'unknown error'));
        }
    }
}
