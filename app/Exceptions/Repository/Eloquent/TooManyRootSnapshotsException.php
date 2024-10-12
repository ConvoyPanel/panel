<?php

namespace App\Exceptions\Repository\Eloquent;

use App\Exceptions\DisplayException;

class TooManyRootSnapshotsException extends DisplayException
{
    public function __construct()
    {
        parent::__construct(
            'The snapshot tree can\'t be constructed because there are too many root snapshots.',
        );
    }
}
