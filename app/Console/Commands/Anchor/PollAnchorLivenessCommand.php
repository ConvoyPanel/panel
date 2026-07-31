<?php

namespace App\Console\Commands\Anchor;

use App\Models\Anchor;
use App\Services\Anchor\AnchorLivenessService;
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
        // Only Anchors that are enrolled but have stopped reporting are worth
        // probing: a fresh heartbeat already tells us everything a probe would,
        // and an Anchor that never enrolled has no secret we could trust.
        $stale = Anchor::query()
            ->whereNotNull('enrolled_at')
            ->where(function ($query) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subMinutes(Anchor::STATUS_TTL_MINUTES));
            })
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No Anchors need probing.');

            return Command::SUCCESS;
        }

        $this->info('Probing Anchors with a stale heartbeat.');

        $stale->each(function (Anchor $anchor) use ($liveness) {
            (new Task($this->output))->render(
                "Anchor {$anchor->name}",
                fn () => $liveness->refresh($anchor),
            );
        });

        return Command::SUCCESS;
    }
}
