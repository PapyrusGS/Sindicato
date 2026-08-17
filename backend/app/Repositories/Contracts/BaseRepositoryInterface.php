<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Obtener todos los registros.
     *
     * @param  array  $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Obtener registros paginados.
     *
     * @param  int    $perPage
     * @param  array  $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Buscar un registro por ID.
     *
     * @param  int    $id
     * @param  array  $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Buscar un registro por ID o lanzar excepción.
     *
     * @param  int    $id
     * @param  array  $columns
     * @return Model
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Crear un nuevo registro.
     *
     * @param  array  $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Actualizar un registro existente.
     *
     * @param  int    $id
     * @param  array  $data
     * @return Model
     */
    public function update(int $id, array $data): Model;

    /**
     * Eliminar un registro.
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Buscar registros por un campo específico.
     *
     * @param  string  $field
     * @param  mixed   $value
     * @param  array   $columns
     * @return Collection
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection;
}
