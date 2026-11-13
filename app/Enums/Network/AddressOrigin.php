<?php

namespace App\Enums\Network;

/**
 * How an address row came to exist.
 *
 * A reconciliation pass needs to know whether a row is Convoy's claim about the
 * world or its record of one, or it cannot tell a row it created from one an
 * operator typed.
 */
enum AddressOrigin: string
{
    /** Materialized from a block's geometry, by the generator or the allocator. */
    case Generated = 'generated';

    /** Written down because an adopted guest was seen using it. */
    case Imported = 'imported';
}
