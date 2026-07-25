<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Enums;

/**
 * Instagram exposes messaging through two different authentication flows, which
 * live on different hosts and require different permission scopes.
 */
enum InstagramLoginType: string
{
    /**
     * Instagram API with Instagram Login. Talks to graph.instagram.com, needs no
     * linked Facebook Page, and requires instagram_business_manage_messages.
     */
    case Instagram = 'instagram';

    /**
     * Instagram API with Facebook Login. Talks to graph.facebook.com, requires
     * the account be linked to a Facebook Page, and needs
     * instagram_manage_messages.
     */
    case Facebook = 'facebook';

    /**
     * The Graph host this flow authenticates against.
     */
    public function host(): string
    {
        return match ($this) {
            self::Instagram => 'https://graph.instagram.com',
            self::Facebook => 'https://graph.facebook.com',
        };
    }

    /**
     * The permission scope required to send messages under this flow.
     */
    public function messagingScope(): string
    {
        return match ($this) {
            self::Instagram => 'instagram_business_manage_messages',
            self::Facebook => 'instagram_manage_messages',
        };
    }
}
