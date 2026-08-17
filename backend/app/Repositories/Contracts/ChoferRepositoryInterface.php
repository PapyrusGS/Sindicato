<?php

namespace App\Repositories\Contracts;

use App\Models\Chofer;
use Illuminate\Database\Eloquent\Collection;

interface ChoferRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveChoferes(): Collection;
}
