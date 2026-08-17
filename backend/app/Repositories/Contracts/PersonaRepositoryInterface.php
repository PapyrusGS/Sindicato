<?php

namespace App\Repositories\Contracts;

use App\Models\Persona;

interface PersonaRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCi(string $ci): ?Persona;
}
