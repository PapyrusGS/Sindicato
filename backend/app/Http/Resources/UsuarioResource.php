<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    /**
     * Transformar el recurso en un array JSON.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'username' => $this->username,
            'estado'   => $this->estado,
            'persona'  => [
                'id'              => $this->persona?->id,
                'primer_nombre'   => $this->persona?->primer_nombre,
                'segundo_nombre'  => $this->persona?->segundo_nombre,
                'primer_apellido' => $this->persona?->primer_apellido,
                'segundo_apellido'=> $this->persona?->segundo_apellido,
                'nombre_completo' => $this->persona?->nombre_completo,
                'ci'              => $this->persona?->ci,
                'celular'         => $this->persona?->celular,
            ],
            'roles'      => $this->whenLoaded('roles', fn() => $this->roles->pluck('nombre')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
