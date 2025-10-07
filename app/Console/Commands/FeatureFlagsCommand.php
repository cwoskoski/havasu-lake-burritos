<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FeatureService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class FeatureFlagsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'features:list
                            {--enabled : Show only enabled features}
                            {--disabled : Show only disabled features}
                            {--complex : Show only complex feature configurations}';

    /**
     * The console command description.
     */
    protected $description = 'List all feature flags and their current status';

    /**
     * Execute the console command.
     */
    public function handle(FeatureService $featureService): int
    {
        $features = Config::get('features', []);

        if (empty($features)) {
            $this->error('No feature flags configured.');

            return self::FAILURE;
        }

        $this->info('Feature Flags Status:');
        $this->newLine();

        $headers = ['Feature', 'Status', 'Type', 'Value'];
        $rows = [];

        foreach ($features as $key => $value) {
            $isEnabled = $featureService->enabled($key);
            $type = is_array($value) ? 'Complex' : 'Boolean';
            $displayValue = $this->getDisplayValue($value, $isEnabled);

            // Apply filters
            if ($this->option('enabled') && ! $isEnabled) {
                continue;
            }

            if ($this->option('disabled') && $isEnabled) {
                continue;
            }

            if ($this->option('complex') && ! is_array($value)) {
                continue;
            }

            $status = $isEnabled ? '<fg=green>✓ Enabled</>' : '<fg=red>✗ Disabled</>';

            $rows[] = [
                $key,
                $status,
                $type,
                $displayValue,
            ];
        }

        if (empty($rows)) {
            $this->warn('No features match the specified filters.');

            return self::SUCCESS;
        }

        $this->table($headers, $rows);

        // Show summary
        $enabledCount = count($featureService->getAllEnabled());
        $totalCount = count($features);
        $disabledCount = $totalCount - $enabledCount;

        $this->newLine();
        $this->info("Summary: {$enabledCount} enabled, {$disabledCount} disabled, {$totalCount} total");

        return self::SUCCESS;
    }

    /**
     * Get display value for the feature.
     */
    private function getDisplayValue(mixed $value, bool $isEnabled): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            if (! $isEnabled && isset($value['enabled'])) {
                return 'disabled';
            }

            $summary = [];
            foreach ($value as $key => $val) {
                if ($key === 'enabled') {
                    continue;
                }
                $summary[] = "{$key}: ".(is_bool($val) ? ($val ? 'true' : 'false') : $val);
            }

            return implode(', ', array_slice($summary, 0, 2)).(count($summary) > 2 ? '...' : '');
        }

        return (string) $value;
    }
}
