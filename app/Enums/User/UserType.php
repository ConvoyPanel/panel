<?php

namespace App\Enums\User;

/**
 * Where an account came from, and therefore what it is entitled to.
 *
 * Convoy has no public signup: a `standard` account is provisioned by the provider's automation
 * (billing panel, directory, `p:make-user`). A `guest` exists only because somebody shared a
 * server with an address that had no account yet, and it must stay distinguishable from a real
 * customer everywhere an operator looks -- lists, filters, counts, the audit log and the API.
 *
 * Stored as a column rather than derived from "owns no servers", because a real customer between
 * cancellations owns no servers either, and a filter cannot ask a derivation.
 */
enum UserType: string
{
    case STANDARD = 'standard';
    case GUEST = 'guest';
}
