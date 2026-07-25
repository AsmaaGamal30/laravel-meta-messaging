<?php

declare(strict_types=1);

namespace AsmaaGamal\MetaMessaging\Contracts;

use AsmaaGamal\MetaMessaging\Responses\MessageResponse;
use AsmaaGamal\MetaMessaging\Transport\MetaRequest;

/**
 * Executes a Graph API call.
 *
 * The one seam between this package and the network. HttpTransport talks to
 * Meta; FakeTransport records requests and replays canned responses, which is
 * what makes Meta::fake() possible without stubbing HTTP.
 */
interface Transport
{
    /**
     * Send the request and return the outcome.
     *
     * Implementations never throw for a Meta-level failure — they return a
     * failed MessageResponse and let the caller decide whether to raise it.
     */
    public function send(MetaRequest $request): MessageResponse;
}
