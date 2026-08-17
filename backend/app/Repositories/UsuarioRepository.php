<?php

namespace App\Repositories;

use App\Models\Usuario;
use App\Repositories\Contracts\UsuarioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UsuarioRepository extends BaseRepository implements UsuarioRepositoryInterface
{
    /**
     * Crear una nueva instancia del repositorio.
     *
     * @param  Usuario  $model
     */
    public function __construct(Usuario $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function findByUsername(string $username): ?Usuario
    {
        return $this->model->where('username', $username)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveUsers(): Collection
    {
        return $this->model->where('estado', true)->get();
    }
}
