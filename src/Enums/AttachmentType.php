<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Enums;

/**
 * Attachment types accepted by the Send API.
 */
enum AttachmentType: string
{
    case Image = 'image';
    case Audio = 'audio';
    case Video = 'video';
    case File = 'file';

    /**
     * Meta's documented size ceiling for this attachment type, in bytes.
     *
     * Images are capped lower than the other media types. Meta rejects anything
     * larger with error 2018047 rather than a size specific message, so the
     * package checks it up front whenever the size is knowable.
     */
    public function maxBytes(): int
    {
        return match ($this) {
            self::Image => 8 * 1024 * 1024,
            self::Audio, self::Video, self::File => 25 * 1024 * 1024,
        };
    }

    /**
     * Human readable size ceiling, for error messages.
     */
    public function maxSizeLabel(): string
    {
        return match ($this) {
            self::Image => '8MB',
            self::Audio, self::Video, self::File => '25MB',
        };
    }
}
