<?php

namespace App\Services\Activity;

use Closure;
use Ramsey\Uuid\Uuid;

class ActivityLogBatchService
{
    protected int $transaction = 0;

    protected ?string $uuid = null;

    /**
     * Returns the UUID of the batch, or null if there is not a batch currently
     * being executed.
     */
    public function uuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * Starts a new batch transaction. If there is already a transaction present
     * this will be nested.
     */
    public function start(?string $uuid = null): void
    {
        if ($this->transaction === 0) {
            $this->uuid = $uuid ?? Uuid::uuid4()->toString();
        }

        $this->transaction++;
    }

    /**
     * Ends a batch transaction, if this is the last transaction in the stack
     * the UUID will be cleared out.
     */
    public function end(): void
    {
        $this->transaction = max(0, $this->transaction - 1);

        if ($this->transaction === 0) {
            $this->uuid = null;
        }
    }

    /**
     * Executes the logic provided within the callback in the scope of an activity
     * log batch transaction.
     *
     * @return mixed
     */
    public function transaction(Closure $callback, ?string $uuid = null)
    {
        $this->start($uuid);
        $result = $callback($this->uuid());
        $this->end();

        return $result;
    }
}
