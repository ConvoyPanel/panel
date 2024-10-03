<?php

namespace Convoy\Exceptions\Repository\Eloquent;

use Convoy\Exceptions\DisplayException;

class TooManyRootSnapshotsException extends DisplayException
{
    public function __construct()
    {
        parent::__construct(
            'The snapshot tree can\'t be constructed because there are too many root snapshots.',
        );
    }
}
