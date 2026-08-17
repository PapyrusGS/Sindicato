<?php

namespace App\Repositories;

use App\Models\Grupo;
use App\Repositories\Contracts\GrupoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GrupoRepository extends BaseRepository implements GrupoRepositoryInterface
{
    public function __construct(Grupo $model)
    {
        parent::__construct($model);
    }

    public function getActiveGrupos(): Collection
    {
        return $this->model->where('estado', true)->get();
    }
}
