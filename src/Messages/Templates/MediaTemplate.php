<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Templates;

use AsmaaGamal\MetaMessaging\Contracts\Button;
use AsmaaGamal\MetaMessaging\Contracts\Template;
use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * An image or video shown full-bleed with buttons beneath it.
 *
 * Facebook Messenger only. The media is referenced either by a Facebook URL
 * (a post or video already on Facebook) or by a reusable attachment ID.
 */
final readonly class MediaTemplate implements Template
{
    public const MAX_BUTTONS = 3;

    /**
     * @param  'image'|'video'  $mediaType
     * @param  array<int, Button>  $buttons
     */
    private function __construct(
        public string $mediaType,
        public ?string $attachmentId = null,
        public ?string $url = null,
        public array $buttons = [],
        public bool $sharable = false,
    ) {}

    /**
     * An image, referenced by reusable attachment ID.
     */
    public static function image(string $attachmentId): self
    {
        return new self('image', attachmentId: $attachmentId);
    }

    /**
     * A video, referenced by reusable attachment ID.
     */
    public static function video(string $attachmentId): self
    {
        return new self('video', attachmentId: $attachmentId);
    }

    /**
     * Media already hosted on Facebook, referenced by its URL.
     *
     * @param  'image'|'video'  $mediaType
     */
    public static function fromFacebookUrl(string $mediaType, string $url): self
    {
        return new self($mediaType, url: $url);
    }

    public function button(Button $button): self
    {
        return new self($this->mediaType, $this->attachmentId, $this->url, [...$this->buttons, $button], $this->sharable);
    }

    public function sharable(bool $sharable = true): self
    {
        return new self($this->mediaType, $this->attachmentId, $this->url, $this->buttons, $sharable);
    }

    public function toPayload(): array
    {
        $element = ['media_type' => $this->mediaType];

        if ($this->attachmentId !== null) {
            $element['attachment_id'] = $this->attachmentId;
        }

        if ($this->url !== null) {
            $element['url'] = $this->url;
        }

        if ($this->buttons !== []) {
            $element['buttons'] = array_map(
                static fn (Button $button): array => $button->toPayload(),
                $this->buttons,
            );
        }

        $payload = [
            'template_type' => 'media',
            'elements' => [$element],
        ];

        if ($this->sharable) {
            $payload['sharable'] = true;
        }

        return $payload;
    }

    public function capability(): Capability
    {
        return Capability::MediaTemplate;
    }

    public function type(): string
    {
        return 'media';
    }

    public function validate(): void
    {
        if ($this->attachmentId === null && $this->url === null) {
            throw MessageValidationException::attachmentSourceRequired();
        }

        if (count($this->buttons) > self::MAX_BUTTONS) {
            throw MessageValidationException::tooManyButtons('media template', count($this->buttons), self::MAX_BUTTONS);
        }

        foreach ($this->buttons as $button) {
            $button->validate();
        }
    }
}
