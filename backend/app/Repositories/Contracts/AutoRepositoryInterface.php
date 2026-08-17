<?php

namespace App\Repositories\Contracts;

use App\Models\Auto;
use Illuminate\Database\Eloquent\Collection;

interface AutoRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveAutos(): Collection;
}
