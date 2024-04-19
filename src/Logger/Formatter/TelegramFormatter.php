<?php

namespace Idea\Logger\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Level;
use Monolog\LogRecord;

class TelegramFormatter implements FormatterInterface
{
    public function format(LogRecord $record)
    {
        $messageFormat = '';
        $message['channel'] = $record->channel;
        $message['level'] = $this->getEmoji($record->level->value).$record->level->name;

        $message['message'] = $record->message;
        $message['file'] = $record->context['file'].':'.$record->context['line'];
        if($_SERVER['REQUEST_URI']) {
            $message['url'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
        if($_SERVER['REQUEST_METHOD']) {
            $message['method'] = $_SERVER['REQUEST_METHOD'];
        }

        foreach ($message as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            $messageFormat .= ' <b>'.$key.': </b>'.$value.PHP_EOL;
        }

        return $messageFormat;
    }

    protected function emojiMap(): array
    {
        return [
            Level::Debug->value => '🔎',
            Level::Info->value => '✏️',
            Level::Notice->value => '⚡️',
            Level::Warning->value => '🌪',
            Level::Error->value => '🔥',
            Level::Critical->value => '💥',
            Level::Alert->value => '🔥💥',
            Level::Emergency->value => '🌪🔥💥',
        ];
    }

    protected function getEmoji(int $level): string
    {
        $levelEmojiMap = $this->emojiMap();

        return $levelEmojiMap[$level];
    }

    public function formatBatch(array $records): string
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }
}
