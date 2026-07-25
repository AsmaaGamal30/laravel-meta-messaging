<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Templates;

use AsmaaGamal\MetaMessaging\Contracts\Template;
use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * A rating prompt (CSAT, NPS, or CES). Facebook Messenger only.
 *
 * Responses arrive on the messaging_feedback webhook rather than as a message.
 */
final readonly class CustomerFeedbackTemplate implements Template
{
    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function __construct(
        public string $title,
        public string $subtitle,
        public string $buttonTitle,
        public string $privacyUrl,
        public array $questions = [],
        public ?int $expiresInDays = null,
    ) {}

    /**
     * @param  string  $privacyUrl  your business privacy policy; Meta requires it
     */
    public static function make(string $title, string $subtitle, string $buttonTitle, string $privacyUrl): self
    {
        return new self($title, $subtitle, $buttonTitle, $privacyUrl);
    }

    /**
     * A 1-5 satisfaction question.
     */
    public function csat(string $id, string $title, ?string $followUpPlaceholder = null): self
    {
        return $this->question($id, 'csat', $title, $followUpPlaceholder);
    }

    /**
     * A 0-10 net promoter question.
     */
    public function nps(string $id, string $title, ?string $followUpPlaceholder = null): self
    {
        return $this->question($id, 'nps', $title, $followUpPlaceholder);
    }

    /**
     * A 1-7 customer effort question.
     */
    public function ces(string $id, string $title, ?string $followUpPlaceholder = null): self
    {
        return $this->question($id, 'ces', $title, $followUpPlaceholder);
    }

    /**
     * How long the prompt stays answerable, in days.
     */
    public function expiresInDays(int $days): self
    {
        return new self($this->title, $this->subtitle, $this->buttonTitle, $this->privacyUrl, $this->questions, $days);
    }

    public function toPayload(): array
    {
        $payload = [
            'template_type' => 'customer_feedback',
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'button_title' => $this->buttonTitle,
            'feedback_screens' => [
                ['questions' => $this->questions],
            ],
            'business_privacy' => ['url' => $this->privacyUrl],
        ];

        if ($this->expiresInDays !== null) {
            $payload['expires_in_days'] = $this->expiresInDays;
        }

        return $payload;
    }

    public function capability(): Capability
    {
        return Capability::CustomerFeedbackTemplate;
    }

    public function type(): string
    {
        return 'customer feedback';
    }

    public function validate(): void
    {
        if ($this->questions === []) {
            throw MessageValidationException::make(
                'empty_message',
                'A customer feedback template needs at least one question.',
            );
        }
    }

    private function question(string $id, string $type, string $title, ?string $followUpPlaceholder): self
    {
        $question = [
            'id' => $id,
            'type' => $type,
            'title' => $title,
        ];

        if ($followUpPlaceholder !== null) {
            $question['follow_up'] = [
                'type' => 'free_form',
                'placeholder' => $followUpPlaceholder,
            ];
        }

        return new self(
            $this->title,
            $this->subtitle,
            $this->buttonTitle,
            $this->privacyUrl,
            [...$this->questions, $question],
            $this->expiresInDays,
        );
    }
}
