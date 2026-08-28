<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Chofer;
use App\Models\ChoferAuto;
use App\Models\Grupo;
use App\Models\Lugar;
use App\Models\Persona;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsistenciaService
{
    /**
     * Hora límite diaria de cierre obligatorio de asistencia (08:00 AM).
     */
    public const HORA_LIMITE_CIERRE = '08:00:00';

    protected RotacionParadaService $rotacionService;

    public function __construct(RotacionParadaService $rotacionService)
    {
        $this->rotacionService = $rotacionService;
    }

    /**
     * Comprobar si el usuario autenticado es Administrador.
     */
    protected function esAdministrador(Usuario $usuario): bool
    {
        return $usuario->roles()->where('nombre', 'Administrador')->exists();
    }

    /**
     * Obtener el ID del grupo asignado a un inspector/chofer.
     */
    protected function obtenerGrupoIdDelInspector(Usuario $usuario): ?int
    {
        $chofer = Chofer::where('persona_id', $usuario->persona_id)->first();
        if (!$chofer) return null;

        return ChoferAuto::where('chofer_id', $chofer->id)->value('grupo_id');
    }

    /**
     * Obtener la planilla de asistencia del día o fecha con verificación de cierre a las 8:00 AM
     * y restricción estricta de grupo según el inspector.
     */
    public function obtenerAsistenciaDelDia(Usuario $usuarioAuth, ?int $grupoIdFilter = null, ?string $fechaFilter = null): array
    {
        $dt = $fechaFilter ? Carbon::parse($fechaFilter) : now();
        $fechaStr = $dt->toDateString();
        $todayStr = now()->toDateString();
        $currentTime = now()->toTimeString();

        // 1. Determinar si la asistencia está bloqueada por haber pasado de las 8:00 AM
        $isPastDate = $fechaStr < $todayStr;
        $isTodayPastEight = ($fechaStr === $todayStr) && ($currentTime >= self::HORA_LIMITE_CIERRE);
        $bloqueado = $isPastDate || $isTodayPastEight;

        // 2. Determinar Grupo (Inspectores SOLO pueden ver SU propio grupo)
        $isAdmin = $this->esAdministrador($usuarioAuth);
        $grupoIdAsignado = $this->obtenerGrupoIdDelInspector($usuarioAuth);

        if (!$isAdmin && $grupoIdAsignado) {
            // Inspector forzado a su propio grupo
            $grupo = Grupo::find($grupoIdAsignado);
        } else if ($grupoIdFilter) {
            $grupo = Grupo::find($grupoIdFilter);
        } else if ($grupoIdAsignado) {
            $grupo = Grupo::find($grupoIdAsignado);
        } else {
            $grupo = Grupo::where('estado', true)->first();
        }

        // 3. Calcular la Parada Asignada mediante Rotación
        $lugar = $this->rotacionService->obtenerParadaDelDia($grupo, $dt);

        // 4. Obtener choferes del grupo
        $choferAutos = ChoferAuto::with(['chofer.persona', 'auto'])
            ->where('grupo_id', $grupo->id)
            ->where('estado', true)
            ->get();

        // 5. Consultar asistencias existentes en la BD
        $asistenciasExistentes = Asistencia::whereDate('fecha_hora', $fechaStr)
            ->where('lugar_id', $lugar?->id)
            ->get()
            ->keyBy('chofer_id');

        // Si ya pasaron las 8:00 AM y aún NO existían registros en la BD, ejecutamos AUTO-GUARDADO de cierre
        if ($bloqueado && $asistenciasExistentes->isEmpty() && $lugar) {
            $this->autoGuardarCierre8AM($choferAutos, $lugar->id, $fechaStr, $usuarioAuth);
            $asistenciasExistentes = Asistencia::whereDate('fecha_hora', $fechaStr)
                ->where('lugar_id', $lugar->id)
                ->get()
                ->keyBy('chofer_id');
        }

        // Mapear datos de choferes
        $choferesData = [];
        foreach ($choferAutos as $ca) {
            $chofer = $ca->chofer;
            if (!$chofer || !$chofer->estado) continue;

            $asistenciaPrevia = $asistenciasExistentes->get($chofer->id);

            $choferesData[] = [
                'chofer_id'       => $chofer->id,
                'persona_id'      => $chofer->persona?->id,
                'nombre_completo' => $chofer->persona?->nombre_completo ?? 'Chofer N/A',
                'ci'              => $chofer->persona?->ci ?? '',
                'celular'         => $chofer->persona?->celular ?? '',
                'placa'           => $ca->auto?->placa ?? 'Sin Auto',
                'vehiculo_info'   => $ca->auto ? "{$ca->auto->marca} {$ca->auto->modelo}" : 'N/A',
                'asistencia'      => $asistenciaPrevia ? (bool) $asistenciaPrevia->asistencia : true,
                'asistencia_id'   => $asistenciaPrevia?->id,
                'hora_registro'   => $asistenciaPrevia?->fecha_hora ? $asistenciaPrevia->fecha_hora->format('H:i:s') : null,
                'inspector_nombre'=> $asistenciaPrevia?->inspector?->persona?->nombre_completo ?? null,
            ];
        }

        // Itinerario semanal del grupo
        $itinerarioSemanal = $this->rotacionService->obtenerItinerarioSemanal($grupo, $dt);

        return [
            'fecha'              => $fechaStr,
            'fecha_formateada'   => $dt->translatedFormat('l, d \d\e F \d\e Y'),
            'hora_actual'        => $currentTime,
            'hora_limite'        => self::HORA_LIMITE_CIERRE,
            'bloqueado'          => $bloqueado,
            'es_admin'           => $isAdmin,
            'motivo_bloqueo'     => $bloqueado ? ($isPastDate ? 'Fecha pasada (modo lectura)' : 'Cierre automático ejecutado (08:00 AM)') : null,
            'grupo'              => [
                'id'          => $grupo->id,
                'nombre'      => $grupo->nombre,
                'descripcion' => $grupo->descripcion,
            ],
            'lugar'              => [
                'id'     => $lugar?->id,
                'nombre' => $lugar?->nombre ?? 'Parada no asignada',
            ],
            'inspector'          => [
                'id'              => $usuarioAuth->id,
                'nombre_completo' => $usuarioAuth->persona?->nombre_completo ?? $usuarioAuth->username,
            ],
            'choferes'           => $choferesData,
            'itinerario_semanal' => $itinerarioSemanal,
        ];
    }

    /**
     * Auto-guardado automático cuando se cumple la hora límite (08:00 AM) sin registro previo.
     */
    protected function autoGuardarCierre8AM($choferAutos, int $lugarId, string $fechaStr, Usuario $usuarioAuth): void
    {
        $choferInspector = Chofer::where('persona_id', $usuarioAuth->persona_id)->first();
        $inspectorId = $choferInspector?->id ?? 1;

        foreach ($choferAutos as $ca) {
            $chofer = $ca->chofer;
            if (!$chofer || !$chofer->estado) continue;

            Asistencia::firstOrCreate(
                [
                    'chofer_id' => $chofer->id,
                    'lugar_id'  => $lugarId,
                    'fecha_hora'=> Carbon::parse($fechaStr . ' 08:00:00'),
                ],
                [
                    'inspector_id' => $inspectorId,
                    'asistencia'   => true,
                    'estado'       => true,
                    'usuarioA'     => 'sistema (cierre 08:00 AM)',
                    'fechaA'       => now(),
                ]
            );
        }
    }

    /**
     * Guardar asistencias masivas con protección de hora límite.
     */
    public function guardarAsistenciasMasivas(array $data, Usuario $usuarioAuth): int
    {
        $fechaStr = $data['fecha'] ?? now()->toDateString();
        $todayStr = now()->toDateString();
        $currentTime = now()->toTimeString();

        $isPastDate = $fechaStr < $todayStr;
        $isTodayPastEight = ($fechaStr === $todayStr) && ($currentTime >= self::HORA_LIMITE_CIERRE);

        if ($isPastDate || $isTodayPastEight) {
            throw ValidationException::withMessages([
                'fecha' => ['La toma de asistencia para esta fecha ya ha sido cerrada obligatoriamente (Hora límite: 08:00 AM). Los datos no pueden ser modificados.'],
            ]);
        }

        return DB::transaction(function () use ($data, $fechaStr, $usuarioAuth) {
            $lugarId = $data['lugar_id'];
            $username = $usuarioAuth->username;

            $choferInspector = Chofer::where('persona_id', $usuarioAuth->persona_id)->first();
            $inspectorChoferId = $choferInspector?->id ?? 1;

            $guardados = 0;

            foreach ($data['asistencias'] as $item) {
                $asistenciaBool = filter_var($item['asistencia'], FILTER_VALIDATE_BOOLEAN);

                $asistencia = Asistencia::where('chofer_id', $item['chofer_id'])
                    ->where('lugar_id', $lugarId)
                    ->whereDate('fecha_hora', $fechaStr)
                    ->first();

                if ($asistencia) {
                    $asistencia->update([
                        'asistencia'   => $asistenciaBool,
                        'inspector_id' => $inspectorChoferId,
                        'usuarioA'     => $username,
                        'fechaA'       => now(),
                    ]);
                } else {
                    Asistencia::create([
                        'chofer_id'    => $item['chofer_id'],
                        'lugar_id'     => $lugarId,
                        'inspector_id' => $inspectorChoferId,
                        'fecha_hora'   => Carbon::parse($fechaStr . ' ' . now()->toTimeString()),
                        'asistencia'   => $asistenciaBool,
                        'estado'       => true,
                        'usuarioA'     => $username,
                        'fechaA'       => now(),
                    ]);
                }

                $guardados++;
            }

            return $guardados;
        });
    }

    /**
     * Consultar el historial detallado de asistencias por fecha seleccionada en el calendario,
     * restringido al grupo del inspector (a menos que sea Administrador).
     */
    public function consultarHistorialPorFecha(Usuario $usuarioAuth, string $fecha, ?int $grupoIdFilter = null): array
    {
        $dt = Carbon::parse($fecha);
        $isAdmin = $this->esAdministrador($usuarioAuth);
        $grupoIdAsignado = $this->obtenerGrupoIdDelInspector($usuarioAuth);

        // Determinar qué grupos consultar
        if (!$isAdmin && $grupoIdAsignado) {
            // Inspector forzado a ver solo su grupo
            $grupos = Grupo::where('id', $grupoIdAsignado)->get();
        } else if ($grupoIdFilter) {
            $grupos = Grupo::where('id', $grupoIdFilter)->get();
        } else {
            $grupos = Grupo::where('estado', true)->get();
        }

        $resultadosPorGrupo = [];

        foreach ($grupos as $grupo) {
            $lugar = $this->rotacionService->obtenerParadaDelDia($grupo, $dt);

            $asistencias = Asistencia::with(['chofer.persona', 'inspector.persona'])
                ->whereDate('fecha_hora', $fecha)
                ->where('lugar_id', $lugar?->id)
                ->get();

            $resultadosPorGrupo[] = [
                'grupo_id'     => $grupo->id,
                'grupo_nombre' => $grupo->nombre,
                'parada'       => $lugar?->nombre ?? 'Parada N/A',
                'lugar_id'     => $lugar?->id,
                'total'        => $asistencias->count(),
                'presentes'    => $asistencias->where('asistencia', true)->count(),
                'ausentes'     => $asistencias->where('asistencia', false)->count(),
                'asistencias'  => $asistencias->map(fn($a) => [
                    'id'              => $a->id,
                    'chofer'          => $a->chofer?->persona?->nombre_completo ?? 'N/A',
                    'ci'              => $a->chofer?->persona?->ci ?? '',
                    'asistencia'      => (bool) $a->asistencia,
                    'inspector'       => $a->inspector?->persona?->nombre_completo ?? 'Sistema',
                    'hora'            => $a->fecha_hora?->format('H:i:s'),
                ]),
            ];
        }

        return [
            'fecha'            => $fecha,
            'fecha_formateada' => $dt->translatedFormat('l, d \d\e F \d\e Y'),
            'es_admin'         => $isAdmin,
            'grupos'           => $resultadosPorGrupo,
        ];
    }
}
