<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Buttons;

use AsmaaGamal\MetaMessaging\Contracts\Button;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * Launches an Instant Game.
 */
final readonly class GamePlayButton implements Button
{
    public const TITLE_LIMIT = 20;

    /**
     * @param  array<string, mixed>  $gameMetadata
     */
    private function __construct(
        public string $title,
        public ?string $payload = null,
        public array $gameMetadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $gameMetadata  e.g. ['player_id' => '...']
     */
    public static function make(string $title, ?string $payload = null, array $gameMetadata = []): self
    {
        return new self($title, $payload, $gameMetadata);
    }

    public function toPayload(): array
    {
        $payload = [
            'type' => 'game_play',
            'title' => $this->title,
        ];

        if ($this->payload !== null) {
            $payload['payload'] = $this->payload;
        }

        if ($this->gameMetadata !== []) {
            $payload['game_metadata'] = $this->gameMetadata;
        }

        return $payload;
    }

    public function validate(): void
    {
        if (mb_strlen($this->title) > self::TITLE_LIMIT) {
            throw MessageValidationException::titleTooLong('button_title_too_long', $this->title, self::TITLE_LIMIT);
        }
    }
}
