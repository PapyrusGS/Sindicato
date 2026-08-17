<?php

namespace App\Repositories\Contracts;

use App\Models\Grupo;
use Illuminate\Database\Eloquent\Collection;

interface GrupoRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveGrupos(): Collection;
}
