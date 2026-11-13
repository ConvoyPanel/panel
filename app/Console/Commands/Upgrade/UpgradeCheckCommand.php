<?php

namespace Convoy\Console\Commands\Upgrade;

use Convoy\Services\Upgrade\UpgradeCheckService;
use Illuminate\Console\Command;

class UpgradeCheckCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'convoy:upgrade-check {--json : Print the items as JSON}';

    /**
     * @var string
     */
    protected $description = 'Show what upgrading to Convoy 6 will do with this panel\'s data. Changes nothing.';

    public function handle(UpgradeCheckService $check): int
    {
        $items = $check->items();

        if ($this->option('json')) {
            $this->line(json_encode(['data' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $this->line('What Convoy 6 will do with this panel\'s data. Nothing here is changed by this check.');

        foreach ($items as $item) {
            $this->newLine();

            $label = match (true) {
                ! $item['affected'] => '<fg=green>nothing here</>',
                $item['effect'] === UpgradeCheckService::DROPPED => '<fg=red>dropped</>',
                default => '<fg=yellow>changed</>',
            };

            $this->line("<options=bold>{$item['title']}</> [{$label}]");
            $this->line('  '.$item['summary']);

            foreach ($item['details'] as $detail) {
                $this->line('  - '.$detail);
            }

            if ($item['action']) {
                $this->line('  <fg=cyan>What to do:</> '.$item['action']);
            }
        }

        $affected = count(array_filter($items, fn (array $item) => $item['affected']));

        $this->newLine();
        $this->info($affected === 0
            ? 'Nothing on this panel is dropped or changed by the upgrade.'
            : "{$affected} of ".count($items).' items apply to this panel.');

        return Command::SUCCESS;
    }
}
