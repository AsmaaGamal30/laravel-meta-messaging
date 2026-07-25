<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Validation;

use AsmaaGamal\MetaMessaging\Channels\MessagingChannel;
use AsmaaGamal\MetaMessaging\Contracts\Validator;
use AsmaaGamal\MetaMessaging\Exceptions\MessageValidationException;
use AsmaaGamal\MetaMessaging\Exceptions\MetaMessagingException;
use AsmaaGamal\MetaMessaging\Messages\MessageBuilder;

/**
 * Runs every pre-flight check, in order, stopping at the first failure.
 *
 * Order matters: a missing recipient is reported before a size limit, and an
 * unsupported feature before a limit that would not apply anyway.
 */
final class ValidationPipeline
{
    /** @var array<int, Validator> */
    private array $validators;

    /**
     * @param  array<int, Validator>|null  $validators  overrides the default chain
     */
    public function __construct(
        private readonly bool $enabled = true,
        ?array $validators = null,
        private readonly ReactionValidator $reactions = new ReactionValidator,
    ) {
        $this->validators = $validators ?? [
            new RecipientValidator,
            new CapabilityValidator,
            new DeprecatedTagValidator,
            new ConstraintValidator,
        ];
    }

    /**
     * @throws MetaMessagingException
     */
    public function validate(MessageBuilder $message, MessagingChannel $channel): void
    {
        if (! $this->enabled) {
            return;
        }

        foreach ($this->validators as $validator) {
            $validator->validate($message, $channel);
        }
    }

    /**
     * Reactions do not go through the message builder, so they get their own
     * entry point.
     *
     * @throws MessageValidationException
     */
    public function validateReaction(string $reaction, MessagingChannel $channel): void
    {
        if ($this->enabled) {
            $this->reactions->validate($reaction, $channel);
        }
    }

    /**
     * Append an application-specific check to the chain.
     */
    public function push(Validator $validator): self
    {
        $this->validators[] = $validator;

        return $this;
    }
}
