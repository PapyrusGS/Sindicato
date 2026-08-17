<?php

namespace App\Repositories\Contracts;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;

interface UsuarioRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Buscar usuario por username.
     *
     * @param  string  $username
     * @return Usuario|null
     */
    public function findByUsername(string $username): ?Usuario;

    /**
     * Obtener usuarios activos.
     *
     * @return Collection
     */
    public function getActiveUsers(): Collection;
}
