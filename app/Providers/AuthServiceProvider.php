<?php

namespace App\Providers;

use App\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => \App\Policies\UserPolicy::class,
        \App\PeticaoNormalizada::class => \App\Policies\PeticaoPolicy::class,
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('access-admin', function (User $user) {
            return $user->isAdmin();
        });

        // Exportar/Importar modelo (pacote JSON): so o ADM, que cria no local e leva para producao.
        Gate::define('transfer-models', function (User $user) {
            return $user->nivel_usu === 'ADM';
        });
    }
}
