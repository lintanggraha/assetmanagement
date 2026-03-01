<?php

namespace App\Console\Commands;

use App\Services\AssetDiscoveryService;
use App\User;
use Illuminate\Console\Command;

class RunScheduledDiscoveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asset:discover-scheduled {--user= : Run for specific user id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled asset discovery from legacy catalog sources';

    /**
     * Execute the console command.
     *
     * @param  \App\Services\AssetDiscoveryService  $service
     * @return int
     */
    public function handle(AssetDiscoveryService $service)
    {
        $userOption = $this->option('user');

        $usersQuery = User::query()->where('is_active', true);
        if ($userOption) {
            $usersQuery->where('id', (int) $userOption);
        } else {
            $usersQuery->whereIn('role', ['superadmin', 'admin', 'operator']);
        }

        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            $this->warn('No eligible users found for scheduled discovery.');
            return 0;
        }

        $success = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $run = $service->runForUser($user->id, [
                    'scope' => 'Scheduled Daily Catalog Discovery',
                    'source_mode' => 'catalog_sync',
                    'include_catalog' => true,
                    'manual_payload' => '',
                    'actor_user_id' => null,
                ]);

                $success++;
                $this->info(sprintf(
                    'User #%d (%s): run %s completed [%s].',
                    $user->id,
                    $user->email,
                    $run->run_uuid,
                    $run->status
                ));
            } catch (\Throwable $exception) {
                $failed++;
                $this->error(sprintf(
                    'User #%d (%s): discovery failed (%s)',
                    $user->id,
                    $user->email,
                    $exception->getMessage()
                ));
            }
        }

        $this->line(sprintf('Discovery schedule done. success=%d failed=%d', $success, $failed));
        return $failed > 0 ? 1 : 0;
    }
}

