<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Auditoria;
use App\Models\Chofer;
use App\Models\ChoferAuto;
use App\Models\Multa;
use App\Models\Obligacion;
use App\Models\ObligacionChofer;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Usuario;
use Carbon\Carbon;

class AuditoriaService
{
    protected function roles(Usuario $usuario): array
    {
        return $usuario->roles()->pluck('nombre')->toArray();
    }

    /**
     * Obtener los IDs de choferes asignados al grupo del usuario autenticado.
     */
    protected function obtenerChoferIdsDelGrupo(Usuario $usuario): array
    {
        $chofer = Chofer::where('persona_id', $usuario->persona_id)->first();
        if (!$chofer) return [];

        $grupoId = ChoferAuto::where('chofer_id', $chofer->id)->value('grupo_id');
        if (!$grupoId) return [];

        return ChoferAuto::where('grupo_id', $grupoId)->pluck('chofer_id')->toArray();
    }

    /**
     * Consultar y listar los registros de auditoría aplicando matriz de alcance por rol.
     */
    public function listarAuditorias(array $filters, Usuario $usuarioAuth)
    {
        $query = Auditoria::with(['persona']);

        $rolesUser = $this->roles($usuarioAuth);
        $esAdmin = in_array('Administrador', $rolesUser);
        $esJefe = in_array('Jefe de Grupo', $rolesUser);
        $esInspector = in_array('Inspector', $rolesUser);
        $esTesorero = in_array('Tesorero', $rolesUser);

        // ─── APLICA RESTRICCIÓN DE ALCANCE POR ROL ───────────────────
        if (!$esAdmin && !$esJefe) {
            $choferIdsGrupo = $this->obtenerChoferIdsDelGrupo($usuarioAuth);

            if ($esInspector && !$esTesorero) {
                // Inspector: sólo asistencias y multas de su grupo
                $query->whereIn('tabla_nombre', ['asistencias', 'multas'])
                    ->where(function ($sub) use ($choferIdsGrupo) {
                        $asistenciaIds = Asistencia::whereIn('chofer_id', $choferIdsGrupo)->pluck('id')->toArray();
                        $multaIds = Multa::whereIn('chofer_id', $choferIdsGrupo)->pluck('id')->toArray();

                        $sub->where(fn($q) => $q->where('tabla_nombre', 'asistencias')->whereIn('registro_id', $asistenciaIds))
                            ->orWhere(fn($q) => $q->where('tabla_nombre', 'multas')->whereIn('registro_id', $multaIds));
                    });
            } elseif ($esTesorero && !$esInspector) {
                // Tesorero: sólo pagos y obligaciones de su grupo
                $query->whereIn('tabla_nombre', ['pagos', 'pago_obligaciones', 'pago_multas', 'obligaciones', 'obligacion_choferes'])
                    ->where(function ($sub) use ($choferIdsGrupo) {
                        $pagoIds = Pago::whereIn('chofer_id', $choferIdsGrupo)->pluck('id')->toArray();
                        $obChoferIds = ObligacionChofer::whereIn('chofer_id', $choferIdsGrupo)->pluck('id')->toArray();

                        $sub->where(fn($q) => $q->where('tabla_nombre', 'pagos')->whereIn('registro_id', $pagoIds))
                            ->orWhere(fn($q) => $q->where('tabla_nombre', 'obligacion_choferes')->whereIn('registro_id', $obChoferIds))
                            ->orWhereIn('tabla_nombre', ['pago_obligaciones', 'pago_multas', 'obligaciones']);
                    });
            }
        }

        // ─── FILTROS DE BÚSQUEDA ─────────────────────────────────────
        if (!empty($filters['modulo'])) {
            $mod = $filters['modulo'];
            if ($mod === 'asistencias') $query->where('tabla_nombre', 'asistencias');
            elseif ($mod === 'sanciones') $query->where('tabla_nombre', 'multas');
            elseif ($mod === 'pagos') $query->whereIn('tabla_nombre', ['pagos', 'pago_obligaciones', 'pago_multas']);
            elseif ($mod === 'obligaciones') $query->whereIn('tabla_nombre', ['obligaciones', 'obligacion_choferes']);
            elseif ($mod === 'afiliaciones') $query->whereIn('tabla_nombre', ['personas', 'choferes', 'autos', 'propietarios']);
            elseif ($mod === 'seguridad') $query->whereIn('tabla_nombre', ['usuarios', 'usuario_roles', 'cambios_rol']);
        }

        if (!empty($filters['accion'])) {
            $query->where('accion', strtoupper($filters['accion']));
        }

        if (!empty($filters['fecha_desde'])) {
            $query->where('fecha_a', '>=', Carbon::parse($filters['fecha_desde'])->startOfDay());
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->where('fecha_a', '<=', Carbon::parse($filters['fecha_hasta'])->endOfDay());
        }

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('tabla_nombre', 'like', "%{$q}%")
                    ->orWhere('campo', 'like', "%{$q}%")
                    ->orWhere('valor_anterior', 'like', "%{$q}%")
                    ->orWhere('valor_nuevo', 'like', "%{$q}%")
                    ->orWhereHas('persona', fn($p) => $p->where('primer_nombre', 'like', "%{$q}%")->orWhere('primer_apellido', 'like', "%{$q}%")->orWhere('ci', 'like', "%{$q}%"));
            });
        }

        $paginated = $query->orderBy('fecha_a', 'desc')->paginate(25);

        // Pre-cargar mapa de choferes/personas afectadas para enriquecer el historial
        $this->enriquecerAuditorias($paginated->getCollection());

        return $paginated;
    }

    /**
     * Enriquece la colección de auditorías con el nombre del chofer/entidad afectada ("a quién se le hizo").
     */
    protected function enriquecerAuditorias($auditoriasCollection): void
    {
        // Extraer IDs por tabla
        $asistenciaIds = $auditoriasCollection->where('tabla_nombre', 'asistencias')->pluck('registro_id')->unique()->toArray();
        $multaIds      = $auditoriasCollection->where('tabla_nombre', 'multas')->pluck('registro_id')->unique()->toArray();
        $pagoIds       = $auditoriasCollection->where('tabla_nombre', 'pagos')->pluck('registro_id')->unique()->toArray();
        $obChoferIds   = $auditoriasCollection->where('tabla_nombre', 'obligacion_choferes')->pluck('registro_id')->unique()->toArray();
        $personaIds    = $auditoriasCollection->where('tabla_nombre', 'personas')->pluck('registro_id')->unique()->toArray();

        $asistenciasMap = !empty($asistenciaIds) ? Asistencia::with('chofer.persona')->whereIn('id', $asistenciaIds)->get()->keyBy('id') : collect();
        $multasMap      = !empty($multaIds) ? Multa::with('chofer.persona')->whereIn('id', $multaIds)->get()->keyBy('id') : collect();
        $pagosMap       = !empty($pagoIds) ? Pago::with('chofer.persona')->whereIn('id', $pagoIds)->get()->keyBy('id') : collect();
        $obChoferesMap  = !empty($obChoferIds) ? ObligacionChofer::with('chofer.persona')->whereIn('id', $obChoferIds)->get()->keyBy('id') : collect();
        $personasMap    = !empty($personaIds) ? Persona::whereIn('id', $personaIds)->get()->keyBy('id') : collect();

        $auditoriasCollection->transform(function ($aud) use ($asistenciasMap, $multasMap, $pagosMap, $obChoferesMap, $personasMap) {
            $afectadoNombre = 'Sistema';
            $afectadoDetalle = "Registro #{$aud->registro_id}";

            if ($aud->tabla_nombre === 'asistencias') {
                $as = $asistenciasMap->get($aud->registro_id);
                $afectadoNombre = $as?->chofer?->persona?->nombre_completo ?? 'Chofer N/A';
                $afectadoDetalle = 'Control de Asistencia del día';
            } elseif ($aud->tabla_nombre === 'multas') {
                $m = $multasMap->get($aud->registro_id);
                $afectadoNombre = $m?->chofer?->persona?->nombre_completo ?? 'Chofer N/A';
                $afectadoDetalle = "Sanción: " . ($m?->motivo ?? 'Infracción');
            } elseif ($aud->tabla_nombre === 'pagos') {
                $p = $pagosMap->get($aud->registro_id);
                $afectadoNombre = $p?->chofer?->persona?->nombre_completo ?? 'Chofer N/A';
                $afectadoDetalle = "Cobro registrado de Bs. " . ($p?->monto_total ?? 0);
            } elseif ($aud->tabla_nombre === 'obligacion_choferes') {
                $oc = $obChoferesMap->get($aud->registro_id);
                $afectadoNombre = $oc?->chofer?->persona?->nombre_completo ?? 'Chofer N/A';
                $afectadoDetalle = 'Asignación de cuota de grupo';
            } elseif ($aud->tabla_nombre === 'personas') {
                $per = $personasMap->get($aud->registro_id);
                $afectadoNombre = $per?->nombre_completo ?? 'Persona N/A';
                $afectadoDetalle = "Persona CI: " . ($per?->ci ?? '');
            }

            // Etiqueta del módulo
            $moduloEtiqueta = match ($aud->tabla_nombre) {
                'asistencias'           => '📋 Control Asistencias',
                'multas'                => '💰 Sanciones / Multas',
                'pagos', 'pago_obligaciones', 'pago_multas' => '💳 Cobros de Caja',
                'obligaciones', 'obligacion_choferes'       => '📅 Cuotas de Grupo',
                'personas', 'choferes', 'autos'            => '👤 Afiliaciones & Autos',
                default                 => '⚙️ Sistema & Seguridad',
            };

            $aud->enriquecido = [
                'ejecutor_nombre' => $aud->persona?->nombre_completo ?? 'Sistema',
                'ejecutor_ci'     => $aud->persona?->ci ?? '',
                'afectado_nombre' => $afectadoNombre,
                'afectado_detalle'=> $afectadoDetalle,
                'modulo_etiqueta' => $moduloEtiqueta,
            ];

            return $aud;
        });
    }

    /**
     * Módulos auxiliares disponibles para filtrar según el rol del usuario.
     */
    public function obtenerAuxiliares(Usuario $usuarioAuth): array
    {
        $rolesUser = $this->roles($usuarioAuth);
        $esAdmin = in_array('Administrador', $rolesUser);
        $esJefe = in_array('Jefe de Grupo', $rolesUser);
        $esInspector = in_array('Inspector', $rolesUser);
        $esTesorero = in_array('Tesorero', $rolesUser);

        $modulos = [];

        if ($esAdmin || $esJefe) {
            $modulos = [
                ['key' => 'asistencias',  'label' => '📋 Control de Asistencias'],
                ['key' => 'sanciones',    'label' => '💰 Sanciones e Infracciones'],
                ['key' => 'pagos',        'label' => '💳 Cobros e Historial de Caja'],
                ['key' => 'obligaciones', 'label' => '📅 Obligaciones de Grupo'],
                ['key' => 'afiliaciones', 'label' => '👤 Afiliación y Choferes'],
                ['key' => 'seguridad',   'label' => '🔐 Seguridad y Usuarios'],
            ];
        } else {
            if ($esInspector) {
                $modulos[] = ['key' => 'asistencias', 'label' => '📋 Control de Asistencias'];
                $modulos[] = ['key' => 'sanciones',   'label' => '💰 Sanciones e Infracciones'];
            }
            if ($esTesorero) {
                $modulos[] = ['key' => 'pagos',        'label' => '💳 Cobros e Historial de Caja'];
                $modulos[] = ['key' => 'obligaciones', 'label' => '📅 Obligaciones de Grupo'];
            }
        }

        return [
            'modulos' => $modulos,
            'acciones' => ['CREACION', 'MODIFICACION', 'DESACTIVACION', 'REACTIVACION', 'ELIMINACION'],
            'is_admin_o_jefe' => $esAdmin || $esJefe,
        ];
    }
}
