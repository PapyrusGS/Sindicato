<?php

namespace App\Repositories;

use App\Models\Auto;
use App\Repositories\Contracts\AutoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AutoRepository extends BaseRepository implements AutoRepositoryInterface
{
    public function __construct(Auto $model)
    {
        parent::__construct($model);
    }

    public function getActiveAutos(): Collection
    {
        return $this->model->with(['propietario.persona', 'choferes.persona'])->where('estado', true)->get();
    }
}
