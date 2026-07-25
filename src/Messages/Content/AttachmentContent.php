<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Messages\Content;

use AsmaaGamal\MetaMessaging\Contracts\MessageContent;
use AsmaaGamal\MetaMessaging\Enums\AttachmentType;
use AsmaaGamal\MetaMessaging\Enums\Capability;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;

/**
 * An image, audio clip, video, or file.
 *
 * Meta accepts three sources, and this object holds exactly one of them:
 * a public URL it will fetch, a previously uploaded attachment ID, or a local
 * file uploaded as multipart.
 */
final readonly class AttachmentContent implements MessageContent
{
    private function __construct(
        public AttachmentType $type,
        public ?string $url = null,
        public ?string $attachmentId = null,
        public ?string $path = null,
        public bool $reusable = false,
    ) {}

    /**
     * Have Meta fetch the asset from a public URL.
     */
    public static function fromUrl(AttachmentType $type, string $url, bool $reusable = false): self
    {
        return new self($type, url: $url, reusable: $reusable);
    }

    /**
     * Reuse an asset already uploaded through the Attachment Upload API.
     */
    public static function fromAttachmentId(AttachmentType $type, string $attachmentId): self
    {
        return new self($type, attachmentId: $attachmentId);
    }

    /**
     * Upload a file from the local filesystem as multipart.
     */
    public static function fromPath(AttachmentType $type, string $path, bool $reusable = false): self
    {
        return new self($type, path: $path, reusable: $reusable);
    }

    /**
     * Decide the source from the shape of the given string.
     *
     * A value that parses as an absolute URL is treated as one; an existing file
     * path is uploaded; anything else is assumed to be an attachment ID.
     */
    public static function from(AttachmentType $type, string $source, bool $reusable = false): self
    {
        if (filter_var($source, FILTER_VALIDATE_URL) !== false) {
            return self::fromUrl($type, $source, $reusable);
        }

        if (is_file($source)) {
            return self::fromPath($type, $source, $reusable);
        }

        return self::fromAttachmentId($type, $source);
    }

    public function toPayload(): array
    {
        return [
            'attachment' => [
                'type' => $this->type->value,
                'payload' => $this->payload(),
            ],
        ];
    }

    public function capability(): Capability
    {
        return match ($this->type) {
            AttachmentType::Image => Capability::ImageAttachment,
            AttachmentType::Audio => Capability::AudioAttachment,
            AttachmentType::Video => Capability::VideoAttachment,
            AttachmentType::File => Capability::FileAttachment,
        };
    }

    public function describe(): string
    {
        return $this->type->value.' attachment';
    }

    /**
     * Whether this attachment is uploaded from disk rather than fetched.
     */
    public function isLocalUpload(): bool
    {
        return $this->path !== null;
    }

    /**
     * Confirm the source is usable before the request is built.
     *
     * @throws MessageValidationException
     */
    public function validate(): void
    {
        if ($this->attachmentId !== null) {
            return;
        }

        if ($this->path !== null) {
            $this->validateLocalFile();

            return;
        }

        if ($this->url === null) {
            throw MessageValidationException::attachmentSourceRequired();
        }

        if (filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            throw MessageValidationException::invalidUrl($this->url);
        }
    }

    /**
     * @throws MessageValidationException
     */
    private function validateLocalFile(): void
    {
        if ($this->path === null || ! is_file($this->path) || ! is_readable($this->path)) {
            throw MessageValidationException::localFileUnreadable((string) $this->path);
        }

        $bytes = filesize($this->path);

        if ($bytes !== false && $bytes > $this->type->maxBytes()) {
            throw MessageValidationException::attachmentTooLarge($this->type, $bytes);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        if ($this->attachmentId !== null) {
            return ['attachment_id' => $this->attachmentId];
        }

        if ($this->path !== null) {
            // The binary travels as a multipart field; the payload only carries
            // the reuse flag.
            return $this->reusable ? ['is_reusable' => true] : [];
        }

        $payload = ['url' => (string) $this->url];

        if ($this->reusable) {
            $payload['is_reusable'] = true;
        }

        return $payload;
    }
}
