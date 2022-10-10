<?php

namespace Convoy\Services\Activity;

use Carbon\Carbon;
use Convoy\Enums\Activity\Status;
use Convoy\Models\ActivityLog;
use Convoy\Models\Node;
use Convoy\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use Convoy\Services\ProxmoxService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;

class ActivityRunnerService extends ProxmoxService
{
    protected ActivityLog $activity;

    protected Node $node;

    public function __construct(protected ProxmoxActivityRepository $repository)
    {
    }

    public function setNode(Node $node): self
    {
        $this->node = $node;

        return $this;
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
        Assert::inArray($this->activity->event, array_keys(ActivityLog::$eventTypes));

        if ($this->activity->status === Status::OK || $this->activity->status === Status::ERROR) {
            // we don't want to change something that's already confirmed
            return $this->activity;
        }

        // set runner to error if it went beyond timeout
        $diff = Carbon::parse($this->activity->created_at)->diffInSeconds(Carbon::now());

        if ($diff > ActivityLog::$eventTypes[$this->activity->event]['timeout']) {
            $this->error();

            return $this->activity;
        }

        try {
            $status = $this->repository->setNode($this->node)->getStatus($this->activity->upid);
        } catch (Exception $e) {
            $this->error();

            return $this->activity;
        }

        if (Arr::get($status, 'status') === 'running') {
            $this->start();

            return $this->activity;
        }

        if (Str::lower(Arr::get($status, 'exitstatus')) === 'ok') {
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
