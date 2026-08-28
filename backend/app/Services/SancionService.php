<?php

namespace App\Services;

use App\Models\Chofer;
use App\Models\ChoferAuto;
use App\Models\Grupo;
use App\Models\Lugar;
use App\Models\Multa;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class SancionService
{
    /**
     * Infracciones comunes predefinidas para sugerencia rápida en la UI.
     */
    public const INFRACCIONES_COMUNES = [
        'Saltarse la parada de vuelta',
        'Impuntualidad en horario de salida',
        'Desvío de ruta no autorizado',
        'Exceso de velocidad en tramo regulado',
        'Falta de respeto o conducta inadecuada',
        'Cobro indebido de pasaje',
        'No portar uniforme o identificación del sindicato',
    ];

    /**
     * Comprobar si el usuario autenticado es Administrador.
     */
    protected function esAdministrador(Usuario $usuario): bool
    {
        return $usuario->roles()->where('nombre', 'Administrador')->exists();
    }

    /**
     * Registrar e imponer una nueva sanción (Económica o Castigo Operativo).
     */
    public function registrarSancion(array $data, Usuario $usuarioAuth): Multa
    {
        return DB::transaction(function () use ($data, $usuarioAuth) {
            $choferInspector = Chofer::where('persona_id', $usuarioAuth->persona_id)->first();
            $inspectorId = $choferInspector?->id ?? 1;

            $tipoSancion = strtoupper($data['tipo_sancion']);
            $esEconomica = $tipoSancion === 'ECONOMICA';

            $monto = $esEconomica ? (float) $data['monto'] : 0.00;
            $estadoPago = $esEconomica ? 'PENDIENTE' : 'NO_APLICA';
            $sancionDetalle = $data['sancion_detalle'] ?? ($esEconomica ? "Multa económica de Bs. " . number_format($monto, 2) : 'Castigo operativo aplicado');

            return Multa::create([
                'chofer_id'        => $data['chofer_id'],
                'inspector_id'     => $inspectorId,
                'lugar_id'         => $data['lugar_id'] ?? null,
                'tipo_sancion'     => $tipoSancion,
                'motivo'           => $data['motivo'],
                'sancion_detalle'  => $sancionDetalle,
                'monto'            => $monto,
                'estado_pago'      => $estadoPago,
                'fecha_infraccion' => now(),
                'estado'           => true,
                'usuarioA'         => $usuarioAuth->username,
                'fechaA'           => now(),
            ]);
        });
    }

    /**
     * Actualizar / Corregir una sanción existente (Auditoría automática de cambios).
     */
    public function actualizarSancion(int $id, array $data, Usuario $usuarioAuth): Multa
    {
        return DB::transaction(function () use ($id, $data, $usuarioAuth) {
            $multa = Multa::findOrFail($id);

            $tipoSancion = strtoupper($data['tipo_sancion']);
            $esEconomica = $tipoSancion === 'ECONOMICA';

            $monto = $esEconomica ? (float) $data['monto'] : 0.00;
            $estadoPago = $esEconomica ? ($multa->estado_pago === 'PAGADO' ? 'PAGADO' : 'PENDIENTE') : 'NO_APLICA';
            $sancionDetalle = $data['sancion_detalle'] ?? ($esEconomica ? "Multa económica de Bs. " . number_format($monto, 2) : 'Castigo operativo aplicado');

            $multa->update([
                'chofer_id'       => $data['chofer_id'] ?? $multa->chofer_id,
                'lugar_id'        => $data['lugar_id'] ?? $multa->lugar_id,
                'tipo_sancion'    => $tipoSancion,
                'motivo'          => $data['motivo'],
                'sancion_detalle' => $sancionDetalle,
                'monto'           => $monto,
                'estado_pago'     => $estadoPago,
                'usuarioA'        => $usuarioAuth->username,
                'fechaA'          => now(),
            ]);

            return $multa->fresh(['chofer.persona', 'inspector.persona', 'lugar']);
        });
    }

    /**
     * Listar historial de sanciones con restricciones de grupo para Inspectores.
     */
    public function listarSanciones(array $filters, Usuario $usuarioAuth)
    {
        $query = Multa::with(['chofer.persona', 'inspector.persona', 'lugar'])
            ->where('estado', true);

        $isAdmin = $this->esAdministrador($usuarioAuth);

        // Si no es admin, filtrar choferes del grupo del inspector
        if (!$isAdmin) {
            $choferInspector = Chofer::where('persona_id', $usuarioAuth->persona_id)->first();
            if ($choferInspector) {
                $grupoId = ChoferAuto::where('chofer_id', $choferInspector->id)->value('grupo_id');
                if ($grupoId) {
                    $choferIdsDelGrupo = ChoferAuto::where('grupo_id', $grupoId)
                        ->pluck('chofer_id')
                        ->toArray();
                    $query->whereIn('chofer_id', $choferIdsDelGrupo);
                }
            }
        }

        // Filtros opcionales
        if (!empty($filters['tipo_sancion'])) {
            $query->where('tipo_sancion', strtoupper($filters['tipo_sancion']));
        }

        if (!empty($filters['estado_pago'])) {
            $query->where('estado_pago', strtoupper($filters['estado_pago']));
        }

        if (!empty($filters['chofer_id'])) {
            $query->where('chofer_id', $filters['chofer_id']);
        }

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('motivo', 'like', "%{$q}%")
                    ->orWhere('sancion_detalle', 'like', "%{$q}%")
                    ->orWhereHas('chofer.persona', fn($p) => $p->where('primer_nombre', 'like', "%{$q}%")->orWhere('primer_apellido', 'like', "%{$q}%")->orWhere('ci', 'like', "%{$q}%"));
            });
        }

        return $query->orderBy('fecha_infraccion', 'desc')->paginate(20);
    }

    /**
     * Obtener datos auxiliares para el formulario de sanciones (Choferes de su grupo, Paradas e Infracciones sugeridas).
     */
    public function obtenerAuxiliares(Usuario $usuarioAuth): array
    {
        $isAdmin = $this->esAdministrador($usuarioAuth);

        $choferQuery = ChoferAuto::with(['chofer.persona', 'auto', 'grupo'])
            ->where('estado', true);

        if (!$isAdmin) {
            $choferInspector = Chofer::where('persona_id', $usuarioAuth->persona_id)->first();
            if ($choferInspector) {
                $grupoId = ChoferAuto::where('chofer_id', $choferInspector->id)->value('grupo_id');
                if ($grupoId) {
                    $choferQuery->where('grupo_id', $grupoId);
                }
            }
        }

        $choferesData = $choferQuery->get()->map(fn($ca) => [
            'id'              => $ca->chofer->id,
            'nombre_completo' => $ca->chofer->persona?->nombre_completo ?? 'N/A',
            'ci'              => $ca->chofer->persona?->ci ?? '',
            'placa'           => $ca->auto?->placa ?? 'Sin auto',
            'grupo_nombre'    => $ca->grupo?->nombre ?? '',
        ])->unique('id')->values();

        $lugares = Lugar::where('estado', true)->get(['id', 'nombre']);

        return [
            'choferes'             => $choferesData,
            'paradas'              => $lugares,
            'infracciones_comunes' => self::INFRACCIONES_COMUNES,
        ];
    }
}
