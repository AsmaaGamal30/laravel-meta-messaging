<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Contracts;

use AsmaaGamal\MetaMessaging\Channels\MessagingChannel;
use AsmaaGamal\MetaMessaging\Exceptions\MetaMessagingException;
use AsmaaGamal\MetaMessaging\Messages\MessageBuilder;

/**
 * One pre-flight check.
 *
 * Validators run in order and throw on the first problem they find. Everything
 * they catch is a request Meta would have rejected anyway, so refusing it here
 * saves an API call, a rate-limit slot, and a confusing error.
 */
interface Validator
{
    /**
     * @throws MetaMessagingException
     */
    public function validate(MessageBuilder $message, MessagingChannel $channel): void;
}
