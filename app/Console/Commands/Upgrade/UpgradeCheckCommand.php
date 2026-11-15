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
    protected $description = 'Show the Convoy 6 upgrade checklist for this panel';

    public function handle(UpgradeCheckService $check): int
    {
        $items = $check->items();

        if ($this->option('json')) {
            $this->line(json_encode(['data' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $this->line('<options=bold>Convoy 6 upgrade checklist</>');
        $this->line('<fg=red>Upgrading to Convoy 6 permanently drops or changes the data below.</> Read each item, and finish anything under "What to do" before you upgrade. Back up the database first with `convoy backup`.');

        foreach ($items as $item) {
            $this->newLine();

            $label = match (true) {
                ! $item['affected'] => "<fg=green>doesn't apply</>",
                $item['effect'] === UpgradeCheckService::DROPPED => '<fg=red>dropped</>',
                default => '<fg=yellow>changed</>',
            };

            $this->line("<options=bold>{$item['title']}</> [{$label}]");
            $this->line('  '.$item['summary']);

            foreach ($item['splits'] as $split) {
                $this->line(sprintf('  %s → %s', $split['pool'], self::plural(count($split['blocks']), 'block')));

                foreach (array_slice($split['blocks'], 0, 5) as $block) {
                    $this->line(sprintf('    %-17s  %s', $block['mac'] ?? 'No MAC', self::plural($block['addresses'], 'address', 'addresses')));
                }

                if (count($split['blocks']) > 5) {
                    $this->line(sprintf('    %d more', count($split['blocks']) - 5));
                }
            }

            if ($item['action']) {
                $this->line('  <fg=cyan>What to do:</> '.$item['action']);
            }
        }

        $affected = count(array_filter($items, fn (array $item) => $item['affected']));

        $this->newLine();
        $this->info($affected === 0
            ? 'None of these apply to this panel.'
            : "{$affected} of ".count($items).' apply to this panel.');

        return Command::SUCCESS;
    }

    private static function plural(int $count, string $singular, ?string $plural = null): string
    {
        return $count.' '.($count === 1 ? $singular : ($plural ?? $singular.'s'));
    }
}
