<?php

namespace App\Support\Anchor;

/**
 * The panel's half of docs/provisioner-anchor-contract.md.
 */
final class AnchorSnippetProtocol
{
    /** Write (PUT, with the snippet as the body) or delete (DELETE) one snippet. */
    public const SNIPPET = '/api/v1/snippets/{name}';

    /** An agent that reports this can write and delete provisioner snippets. */
    public const CAPABILITY = 'provisioning.snippets';

    /** The agent refuses a bigger body. */
    public const MAX_BYTES = 1048576;

    public const TIMEOUT_SECONDS = 15;
}
