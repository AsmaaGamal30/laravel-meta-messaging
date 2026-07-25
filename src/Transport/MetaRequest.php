<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Transport;

use AsmaaGamal\MetaMessaging\Enums\Channel;
use AsmaaGamal\MetaMessaging\Support\AccountConfig;
use AsmaaGamal\MetaMessaging\Support\Redactor;

/**
 * One outgoing Graph API call, fully described.
 *
 * Passed to the transport to be executed and to the error mapper to build
 * context when it fails, so both see exactly the same request.
 */
final readonly class MetaRequest
{
    /**
     * @param  string  $path  path after the version segment, e.g. "123/messages"
     * @param  array<string, mixed>  $payload  JSON body
     * @param  array<string, mixed>  $files  local files to send as multipart
     */
    public function __construct(
        public AccountConfig $account,
        public string $path,
        public array $payload,
        public string $method = 'POST',
        public array $files = [],
    ) {}

    public function channel(): Channel
    {
        return $this->account->channel;
    }

    /**
     * The absolute URL, without credentials in the query string.
     */
    public function url(): string
    {
        return $this->account->baseUrl().'/'.ltrim($this->path, '/');
    }

    /**
     * The body actually sent, with the token and appsecret proof attached.
     *
     * @return array<string, mixed>
     */
    public function body(): array
    {
        $body = $this->payload;
        $body['access_token'] = $this->account->token;

        if (($proof = $this->account->appSecretProof()) !== null) {
            $body['appsecret_proof'] = $proof;
        }

        return $body;
    }

    /**
     * The payload as it is safe to log or attach to an exception.
     *
     * @return array<string, mixed>
     */
    public function safePayload(): array
    {
        return Redactor::scrub($this->payload);
    }

    public function hasFiles(): bool
    {
        return $this->files !== [];
    }

    /**
     * A copy of this request against a different path.
     */
    public function withPath(string $path): self
    {
        return new self($this->account, $path, $this->payload, $this->method, $this->files);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'url' => $this->url(),
            'channel' => $this->channel()->value,
            'account' => $this->account->name,
            'version' => $this->account->version->value,
            'payload' => $this->safePayload(),
        ];
    }
}
