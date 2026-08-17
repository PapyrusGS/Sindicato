<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AfiliadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'primer_nombre'    => $this->primer_nombre,
            'segundo_nombre'   => $this->segundo_nombre,
            'primer_apellido'  => $this->primer_apellido,
            'segundo_apellido' => $this->segundo_apellido,
            'nombre_completo'  => $this->nombre_completo,
            'ci'               => $this->ci,
            'celular'          => $this->celular,
            'direccion'        => $this->direccion,
            'estado'           => (bool) $this->estado,
            'auditoria'        => [
                'usuarioA' => $this->usuarioA ?? 'system',
                'fechaA'   => $this->fechaA ? date('d/m/Y', strtotime($this->fechaA)) : ($this->updated_at ? $this->updated_at->format('d/m/Y') : 'N/A'),
            ],
            'usuario'          => [
                'id'       => $this->usuario?->id,
                'username' => $this->usuario?->username,
                'roles'    => $this->usuario?->roles?->pluck('nombre') ?? [],
            ],
            'es_chofer'        => (bool) $this->chofer,
            'es_propietario'   => (bool) $this->propietario,
            'vehiculos'        => $this->formatVehiculos(),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }

    private function formatVehiculos(): array
    {
        $vehiculos = [];

        if ($this->propietario && $this->propietario->autos) {
            foreach ($this->propietario->autos as $auto) {
                $vehiculos[] = [
                    'id'          => $auto->id,
                    'placa'       => $auto->placa,
                    'marca'       => $auto->marca,
                    'modelo'      => $auto->modelo,
                    'gestion'     => $auto->gestion,
                    'tipo'        => 'Propietario',
                    'chofer'      => $auto->choferes->first()?->persona?->nombre_completo ?? 'Sin chofer asignado',
                ];
            }
        }

        if ($this->chofer && $this->chofer->autos) {
            foreach ($this->chofer->autos as $auto) {
                $yaExiste = collect($vehiculos)->contains('id', $auto->id);
                if (!$yaExiste) {
                    $vehiculos[] = [
                        'id'          => $auto->id,
                        'placa'       => $auto->placa,
                        'marca'       => $auto->marca,
                        'modelo'      => $auto->modelo,
                        'gestion'     => $auto->gestion,
                        'tipo'        => 'Conductor',
                        'propietario' => $auto->propietario?->persona?->nombre_completo ?? 'N/A',
                    ];
                }
            }
        }

        return $vehiculos;
    }
}
