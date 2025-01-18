<?php

namespace Braintacle;

use Laminas\Session\Container as Session;

/**
 * Handle flash messages (volatile session messages to be evaluated after
 * redirect).
 *
 * Messages are grouped by type. The type can be an arbitrary string, but common
 * types are defined as public constants. More than 1 message per type can be
 * added. They will be returned as a list.
 */
class FlashMessages
{
    public const Success = 'success';
    public const Error = 'error';

    private const Identifier = __CLASS__;

    public function __construct(private Session $session)
    {
    }

    public function add(string $type, string $message): void
    {
        // Direct modification of the session container will have no effect if
        // the variable does not exist yet. Read/create the variable, modify it
        // and write it back as a whole.
        $messages = $this->session[self::Identifier] ?? [];
        $messages[$type][] = $message;
        $this->session[self::Identifier] = $messages;
        $this->session->setExpirationHops(1, self::Identifier);
    }

    public function get(string $type): array
    {
        return $this->session[self::Identifier][$type] ?? [];
    }
}
