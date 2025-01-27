<?php

namespace Braintacle\Group;

/**
 * Group membership types.
 *
 * Backing values are used in the database representation.
 */
enum Membership: int
{
    /**
     * Automatic group membership, i.e. from a group query.
     */
    case Automatic = 0;

    /**
     * Explicit group membership.
     */
    case Manual = 1;

    /**
     * The client is excluded from a group.
     */
    case Never = 2;
}
