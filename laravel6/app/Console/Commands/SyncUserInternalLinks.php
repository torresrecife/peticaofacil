<?php

namespace App\Console\Commands;

use App\Services\UserSyncService;
use App\User;
use Illuminate\Console\Command;

class SyncUserInternalLinks extends Command
{
    protected $signature = 'usuarios:sync-vinculos {userId?}';
    protected $description = 'Sincroniza favoritos, peticoes e versoes para o user_id novo';

    public function handle(UserSyncService $service)
    {
        $userId = $this->argument('userId');

        if ($userId) {
            $user = User::findOrFail($userId);
            $service->syncInternalReferences($user);
            $this->info('Vinculos sincronizados para o usuario #' . $user->id . '.');

            return 0;
        }

        $count = 0;

        User::whereNotNull('legacy_usuario_id')
            ->orderBy('id')
            ->chunk(200, function ($users) use ($service, &$count) {
                foreach ($users as $user) {
                    $service->syncInternalReferences($user);
                    $count++;
                }
            });

        $this->info('Vinculos sincronizados para ' . $count . ' usuarios.');

        return 0;
    }
}
