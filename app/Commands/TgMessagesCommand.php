<?php

namespace App\Commands;

use App\Application;
use App\Cache\Redis;
use App\Telegram\TelegramApiImpl;
use Predis\Client;

class TgMessagesCommand extends Command
{
    protected Application $app;
    private int $offset;
    private array $oldMessages;
    private Redis $redis;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->offset = 0;
        $this->oldMessages = [];
        $client = new Client([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
        ]);

        $this->redis = new Redis($client);
    }

    public function run(array $options = []): void
    {
        echo json_encode($this->receiveNewMessages());
    }

    protected function getTelegramApiImpl(): TelegramApiImpl
    {
        return new TelegramApiImpl($this->app->env('TELEGRAM_TOKEN'));
    }

    private function receiveNewMessages(): array
    {
        $this->offset = $this->redis->get('tg_messages:offset', 0);
        $result = $this->getTelegramApiImpl()->getMessages($this->offset);
        $this->redis->set('tg_messages:offset', $result['offset'] ?? 0);
        $this->oldMessages = json_decode($this->redis->get('tg_messages:old_messages'), true) ?? [];
        $messages = [];

        foreach ($result['result'] ?? [] as $newMessage) {
            $chatId = $newMessage['chat']['id'] ?? null; // Получаем chatId из нового сообщения
            if ($chatId !== null) {
                if (isset($this->oldMessages[$chatId])) {
                    $this->oldMessages[$chatId] = array_merge($this->oldMessages[$chatId], [$newMessage]);
                } else {
                    $this->oldMessages[$chatId] = [$newMessage];
                }
                $messages[$chatId] = $this->oldMessages[$chatId];
            }
        }

        $this->redis->set('tg_messages:old_messages', json_encode($this->oldMessages));
        return $messages;
    }
}