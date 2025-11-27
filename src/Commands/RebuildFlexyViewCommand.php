<?php

namespace AuroraWebSoftware\FlexyField\Commands;

use AuroraWebSoftware\FlexyField\FlexyField;
use Illuminate\Console\Command;

class RebuildFlexyViewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flexyfield:rebuild-view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force rebuild the FlexyField pivot view and update schema tracking';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Rebuilding FlexyField pivot view...');

        try {
            FlexyField::forceRecreateView();

            $this->info('✓ Pivot view rebuilt successfully');
            $this->info('✓ Schema tracking table updated');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to rebuild view: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
