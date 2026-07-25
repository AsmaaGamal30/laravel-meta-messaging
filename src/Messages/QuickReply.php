<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages;

use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * One tappable chip shown beneath a message.
 *
 * Meta allows at most 13 per message; the builder enforces that.
 */
final readonly class QuickReply
{
    public const TITLE_LIMIT = 20;

    public const PAYLOAD_LIMIT = 1000;

    public const MAX_PER_MESSAGE = 13;

    private function __construct(
        public string $contentType,
        public ?string $title = null,
        public ?string $payload = null,
        public ?string $imageUrl = null,
    ) {}

    /**
     * A normal chip. The payload comes back on the messaging_postbacks webhook.
     */
    public static function text(string $title, string $payload, ?string $imageUrl = null): self
    {
        return new self('text', $title, $payload, $imageUrl);
    }

    /**
     * Ask for the person's phone number, prefilled by Meta.
     */
    public static function phone(): self
    {
        return new self('user_phone_number');
    }

    /**
     * Ask for the person's email address, prefilled by Meta.
     */
    public static function email(): self
    {
        return new self('user_email');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = ['content_type' => $this->contentType];

        if ($this->title !== null) {
            $payload['title'] = $this->title;
        }

        if ($this->payload !== null) {
            $payload['payload'] = $this->payload;
        }

        if ($this->imageUrl !== null) {
            $payload['image_url'] = $this->imageUrl;
        }

        return $payload;
    }

    /**
     * @throws MessageValidationException
     */
    public function validate(): void
    {
        if ($this->title !== null && mb_strlen($this->title) > self::TITLE_LIMIT) {
            throw MessageValidationException::titleTooLong(
                'quick_reply_title_too_long',
                $this->title,
                self::TITLE_LIMIT,
            );
        }
    }
}
