<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Contracts
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Contracts\UsuarioRepositoryInterface;
use App\Repositories\Contracts\PersonaRepositoryInterface;
use App\Repositories\Contracts\ChoferRepositoryInterface;
use App\Repositories\Contracts\AutoRepositoryInterface;
use App\Repositories\Contracts\GrupoRepositoryInterface;

// Implementations
use App\Repositories\BaseRepository;
use App\Repositories\UsuarioRepository;
use App\Repositories\PersonaRepository;
use App\Repositories\ChoferRepository;
use App\Repositories\AutoRepository;
use App\Repositories\GrupoRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registrar los bindings de interfaces a implementaciones.
     */
    public function register(): void
    {
        $this->app->bind(UsuarioRepositoryInterface::class, UsuarioRepository::class);
        $this->app->bind(PersonaRepositoryInterface::class, PersonaRepository::class);
        $this->app->bind(ChoferRepositoryInterface::class, ChoferRepository::class);
        $this->app->bind(AutoRepositoryInterface::class, AutoRepository::class);
        $this->app->bind(GrupoRepositoryInterface::class, GrupoRepository::class);
    }

    /**
     * Bootstrap de servicios.
     */
    public function boot(): void
    {
        //
    }
}
