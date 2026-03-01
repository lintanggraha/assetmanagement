<?php

namespace App\Console\Commands;

use App\Services\AssetPolicyScannerService;
use Illuminate\Console\Command;

class ScanAssetPoliciesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asset:scan-policies {--user= : Scan policy for specific user id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan asset policies and update open/resolved violations';

    /**
     * Execute the console command.
     *
     * @param  \App\Services\AssetPolicyScannerService  $scanner
     * @return int
     */
    public function handle(AssetPolicyScannerService $scanner)
    {
        $userOption = $this->option('user');
        $userId = $userOption ? (int) $userOption : null;

        $stats = $scanner->scan($userId);

        $this->info('Asset policy scan completed.');
        $this->table(
            ['assets_scanned', 'new_open', 'still_open', 'resolved', 'total_open'],
            [[
                $stats['assets_scanned'],
                $stats['new_open'],
                $stats['still_open'],
                $stats['resolved'],
                $stats['total_open'],
            ]]
        );

        return 0;
    }
}

