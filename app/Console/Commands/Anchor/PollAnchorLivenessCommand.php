<?php

namespace App\Console\Commands\Anchor;

use App\Models\Node;
use App\Models\Relay;
use App\Services\Anchor\AnchorLivenessService;
use App\Support\Anchor\AnchorProtocol;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Task;

class PollAnchorLivenessCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Probe Anchors whose heartbeat has gone stale and record the result.';

    /**
     * @var string
     */
    protected $signature = 'anchors:poll';

    public function handle(AnchorLivenessService $liveness): int
    {
        $cutoff = now()->subMinutes(AnchorProtocol::STATUS_TTL_MINUTES);

        /*
         * Only installations that are enrolled but have stopped reporting are
         * worth probing: a fresh heartbeat already tells us everything a probe
         * would, and one that never enrolled has no secret we could trust.
         *
         * Machines still in `anchor_enrollments` are deliberately not probed --
         * nobody has told us an address to probe them at, and that is what
         * approval establishes.
         */
        $stale = Node::query()
            ->whereNotNull('agent_enrolled_at')
            ->where(fn ($query) => $query
                ->whereNull('agent_last_seen_at')
                ->orWhere('agent_last_seen_at', '<', $cutoff))
            ->get()
            ->concat(
                Relay::query()
                    ->whereNotNull('enrolled_at')
                    ->where(fn ($query) => $query
                        ->whereNull('last_seen_at')
                        ->orWhere('last_seen_at', '<', $cutoff))
                    ->get()
            );

        if ($stale->isEmpty()) {
            $this->info('No Anchors need probing.');

            return Command::SUCCESS;
        }

        $this->info('Probing Anchors with a stale heartbeat.');

        $stale->each(function (Node|Relay $installation) use ($liveness) {
            (new Task($this->output))->render(
                "Anchor {$installation->anchorName()}",
                fn () => $liveness->refresh($installation),
            );
        });

        return Command::SUCCESS;
    }
}
