<?php

namespace App\Services\Activity;

use App\Enums\Activity\Status;
use App\Models\ActivityLog;
use App\Models\Node;
use App\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use App\Services\ProxmoxService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;

class ActivityRunnerService extends ProxmoxService
{
    protected ActivityLog $activity;

    public function __construct(protected ProxmoxActivityRepository $repository)
    {

    }

    public function setActivity(ActivityLog $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    /*
     * Automatic status updating
     */

    public function refresh(): ActivityLog
    {
        Assert::isInstanceOf($this->activity, ActivityLog::class);
        Assert::isInstanceOf($this->node, Node::class);

        if (is_null($this->activity->upid))
        {
            $this->error();

            return $this->activity;
        }

        try {
            $status = $this->repository->setNode($this->node)->getStatus($this->activity->upid);
        } catch (Exception $e) {
            $this->error();

            return $this->activity;
        }

        if (Arr::get($status, 'status') === 'running')
        {
            $this->start();

            return $this->activity;
        }

        if (Str::lower(Arr::get($status, 'exitstatus')) === 'ok')
        {
            $this->end();
        } else {
            $this->error();
        }

        return $this->activity;
    }

    /*
     *  These functions below are manual controls for updating the activity status
    */

    public function start()
    {
        Assert::isInstanceOf($this->activity, ActivityLog::class);

        $this->activity->status = Status::RUNNING;
        $this->activity->save();
    }

    public function error()
    {
        Assert::isInstanceOf($this->activity, ActivityLog::class);


        $this->activity->status = Status::ERROR;
        $this->activity->save();
    }

    public function end()
    {
        Assert::isInstanceOf($this->activity, ActivityLog::class);

        $this->activity->status = Status::OK;
        $this->activity->save();
    }
}