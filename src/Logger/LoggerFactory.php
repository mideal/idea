<?php

namespace Idea\Logger;

use Idea\Logger\Formatter\TelegramFormatter;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Handler\TelegramBotHandler;

class LoggerFactory
{
    private Logger $logger;
    private string $loggerDefault = 'file';
    private array $loggers = [
        'file' => 'file',
        'telegram' => 'telegram'
    ];

    public function __construct()
    {
        $this->logger = new Logger($_ENV['APP_ENV'] ?? 'production');
    }

    /**
     * @throws Exception
     */
    public function get(string $loggerName = null): Logger
    {
        if ($loggerName && !$this->loggers[$loggerName]) {
            throw new Exception('No logger exist');
        }
        $logger = $this->loggers[$loggerName] ?? $_ENV['LOG_DEFAULT'] ?? $this->loggerDefault;
        if ($logger == 'file') {
            $this->setFileHandler();
        } elseif ($logger == 'telegram') {
            $this->setTelegramHandler();
        }
        return $this->logger;
    }

    private function setTelegramHandler(): void
    {
        $telegramHandler = new TelegramBotHandler($_ENV['TELEGRAM_BOT_TOKEN'], $_ENV['TELEGRAM_CHAT_ID'], parseMode: 'HTML');
        $telegramHandler->setFormatter(new TelegramFormatter());
        $this->logger->pushHandler($telegramHandler);
    }

    protected function setFileHandler(): void
    {
        $streamHandler = new StreamHandler($_SERVER['DOCUMENT_ROOT'] . '/log/log.log');
        $this->logger->pushHandler($streamHandler);
    }
}
