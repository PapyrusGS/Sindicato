<?php

namespace App\Repositories;

use App\Models\Chofer;
use App\Repositories\Contracts\ChoferRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ChoferRepository extends BaseRepository implements ChoferRepositoryInterface
{
    public function __construct(Chofer $model)
    {
        parent::__construct($model);
    }

    public function getActiveChoferes(): Collection
    {
        return $this->model->with(['persona', 'autos'])->where('estado', true)->get();
    }
}
