<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Buttons;

use AsmaaGamal\MetaMessaging\Contracts\Button;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * Opens a web page, either in the browser or in a Messenger webview.
 */
final readonly class UrlButton implements Button
{
    public const TITLE_LIMIT = 20;

    private function __construct(
        public string $title,
        public string $url,
        public ?string $webviewHeightRatio = null,
        public bool $messengerExtensions = false,
        public ?string $fallbackUrl = null,
        public bool $hideShareButton = false,
    ) {}

    public static function make(string $title, string $url): self
    {
        return new self($title, $url);
    }

    /**
     * Open inside a webview at the given height.
     *
     * @param  'compact'|'tall'|'full'  $ratio
     */
    public function webview(string $ratio = 'full', bool $messengerExtensions = false, ?string $fallbackUrl = null): self
    {
        return new self(
            $this->title,
            $this->url,
            $ratio,
            $messengerExtensions,
            $fallbackUrl,
            $this->hideShareButton,
        );
    }

    /**
     * Hide the share button in the webview header.
     */
    public function withoutShareButton(): self
    {
        return new self(
            $this->title,
            $this->url,
            $this->webviewHeightRatio,
            $this->messengerExtensions,
            $this->fallbackUrl,
            true,
        );
    }

    public function toPayload(): array
    {
        $payload = [
            'type' => 'web_url',
            'title' => $this->title,
            'url' => $this->url,
        ];

        if ($this->webviewHeightRatio !== null) {
            $payload['webview_height_ratio'] = $this->webviewHeightRatio;
        }

        if ($this->messengerExtensions) {
            $payload['messenger_extensions'] = true;
        }

        if ($this->fallbackUrl !== null) {
            $payload['fallback_url'] = $this->fallbackUrl;
        }

        if ($this->hideShareButton) {
            $payload['webview_share_button'] = 'hide';
        }

        return $payload;
    }

    public function validate(): void
    {
        if (mb_strlen($this->title) > self::TITLE_LIMIT) {
            throw MessageValidationException::titleTooLong('button_title_too_long', $this->title, self::TITLE_LIMIT);
        }

        if (filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            throw MessageValidationException::invalidUrl($this->url);
        }
    }
}
