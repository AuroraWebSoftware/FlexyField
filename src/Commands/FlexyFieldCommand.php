<?php

namespace AuroraWebSoftware\FlexyField\Commands;

use Illuminate\Console\Command;

class FlexyFieldCommand extends Command
{
    public $signature = 'flexyfield';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
