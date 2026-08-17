<?php

namespace App\Repositories;

use App\Models\Persona;
use App\Repositories\Contracts\PersonaRepositoryInterface;

class PersonaRepository extends BaseRepository implements PersonaRepositoryInterface
{
    public function __construct(Persona $model)
    {
        parent::__construct($model);
    }

    public function findByCi(string $ci): ?Persona
    {
        return $this->model->where('ci', $ci)->first();
    }
}
