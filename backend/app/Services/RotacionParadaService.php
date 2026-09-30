<?php

namespace App\Services;

use App\Models\Grupo;
use App\Models\Lugar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class RotacionParadaService
{
    /**
     * Claves para el almacenamiento en cache diario.
     */
    public const CACHE_KEY_PARADAS = 'rotacion_paradas_activas';
    public const CACHE_KEY_GRUPOS = 'rotacion_grupos_activos';

    /**
     * Lunes de referencia para el cálculo de semanas de rotación (03 de Agosto de 2026).
     */
    public const LUNES_REFERENCIA = '2026-08-03';

    /**
     * Cache local en memoria para evitar múltiples lecturas dentro de la misma petición.
     */
    protected ?Collection $paradasMemoria = null;
    protected ?Collection $gruposMemoria = null;

    /**
     * Obtener la lista ordenada de paradas (lugares) activas desde la base de datos,
     * almacenándolas en cache durante 1 día para optimizar el rendimiento.
     *
     * @return Collection<int, Lugar>
     */
    public function obtenerParadasOrdenadas(): Collection
    {
        if ($this->paradasMemoria !== null) {
            return $this->paradasMemoria;
        }

        $this->paradasMemoria = Cache::remember(
            self::CACHE_KEY_PARADAS,
            now()->addDay(),
            fn () => Lugar::where('estado', true)->orderBy('id', 'asc')->get()
        );

        return $this->paradasMemoria;
    }

    /**
     * Obtener la lista ordenada de grupos activos desde la base de datos,
     * almacenándolos en cache durante 1 día.
     *
     * @return Collection<int, Grupo>
     */
    public function obtenerGruposOrdenados(): Collection
    {
        if ($this->gruposMemoria !== null) {
            return $this->gruposMemoria;
        }

        $this->gruposMemoria = Cache::remember(
            self::CACHE_KEY_GRUPOS,
            now()->addDay(),
            fn () => Grupo::where('estado', true)->orderBy('id', 'asc')->get()
        );

        return $this->gruposMemoria;
    }

    /**
     * Limpiar el cache de paradas y grupos de rotación.
     */
    public function limpiarCache(): void
    {
        $this->paradasMemoria = null;
        $this->gruposMemoria = null;
        Cache::forget(self::CACHE_KEY_PARADAS);
        Cache::forget(self::CACHE_KEY_GRUPOS);
    }

    /**
     * Calcular la parada correspondiente para un grupo en una fecha determinada.
     *
     * @param  Grupo|int|string  $grupo  (Instancia de Grupo, ID o Nombre 'Grupo A')
     * @param  Carbon|string|null  $fecha
     * @return Lugar|null
     */
    public function obtenerParadaDelDia(Grupo|int|string $grupo, Carbon|string $fecha = null): ?Lugar
    {
        $dt = $fecha ? Carbon::parse($fecha) : now();

        $grupos = $this->obtenerGruposOrdenados();
        $paradas = $this->obtenerParadasOrdenadas();

        if ($grupos->isEmpty() || $paradas->isEmpty()) {
            return null;
        }

        // 1. Obtener índice del grupo en la lista ordenada
        $grupoIndex = null;

        if ($grupo instanceof Grupo) {
            $grupoIndex = $grupos->search(fn ($g) => $g->id === $grupo->id);
        } elseif (is_numeric($grupo)) {
            $grupoId = (int) $grupo;
            $grupoIndex = $grupos->search(fn ($g) => $g->id === $grupoId);
        } elseif (is_string($grupo)) {
            // Buscar por coincidencia exacta o parcial de nombre
            $grupoIndex = $grupos->search(fn ($g) => strcasecmp($g->nombre, $grupo) === 0);
            if ($grupoIndex === false || $grupoIndex === null) {
                $grupoIndex = $grupos->search(
                    fn ($g) => stripos($g->nombre, $grupo) !== false || stripos($grupo, $g->nombre) !== false
                );
            }
        }

        if ($grupoIndex === false || $grupoIndex === null) {
            $grupoIndex = 0; // Fallback al primer grupo
        }

        // 2. Determinar índice del día de la semana (Lunes=0, Martes=1, Miércoles=2, Jueves=3, Viernes=4)
        $dayOfWeek = $dt->dayOfWeekIso; // Lunes=1, Domingo=7
        $dayIndex = match ($dayOfWeek) {
            1 => 0, // Lunes
            2 => 1, // Martes
            3 => 2, // Miércoles
            4 => 3, // Jueves
            5 => 4, // Viernes
            default => 0, // Sábado/Domingo toma el ciclo base
        };

        // 3. Calcular semanas transcurridas desde el Lunes de Referencia
        $refLunes = Carbon::parse(self::LUNES_REFERENCIA)->startOfWeek(Carbon::MONDAY);
        $currLunes = $dt->copy()->startOfWeek(Carbon::MONDAY);
        $weeksPassed = (int) floor($refLunes->diffInDays($currLunes) / 7);

        // 4. Algoritmo de Rotación matemática modular
        $totalParadas = $paradas->count();
        $paradaIndex = ($grupoIndex + $weeksPassed + $dayIndex) % $totalParadas;
        if ($paradaIndex < 0) {
            $paradaIndex = ($paradaIndex % $totalParadas + $totalParadas) % $totalParadas;
        }

        return $paradas->values()->get($paradaIndex);
    }

    /**
     * Obtener el itinerario semanal de un grupo para la semana de la fecha dada.
     *
     * @param  Grupo|int|string  $grupo
     * @param  Carbon|string|null  $fecha
     * @return array
     */
    public function obtenerItinerarioSemanal(Grupo|int|string $grupo, Carbon|string $fecha = null): array
    {
        $dt = $fecha ? Carbon::parse($fecha) : now();
        $lunes = $dt->copy()->startOfWeek(Carbon::MONDAY);

        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        $itinerario = [];

        for ($i = 0; $i < 5; $i++) {
            $diaFecha = $lunes->copy()->addDays($i);
            $lugar = $this->obtenerParadaDelDia($grupo, $diaFecha);

            $itinerario[] = [
                'dia'        => $diasSemana[$i],
                'fecha'      => $diaFecha->toDateString(),
                'fecha_form' => $diaFecha->format('d/m/Y'),
                'parada'     => $lugar?->nombre ?? 'N/A',
                'lugar_id'   => $lugar?->id,
            ];
        }

        return $itinerario;
    }

    /**
     * Obtener el payload completo de rotación (paradas, grupos, asignaciones del día e itinerario semanal),
     * ideal para sincronización diaria con la aplicación móvil y clientes web.
     *
     * @param  Carbon|string|null  $fecha
     * @return array
     */
    public function obtenerPayloadRotacion(Carbon|string|null $fecha = null): array
    {
        $dt = $fecha ? Carbon::parse($fecha) : now();
        $grupos = $this->obtenerGruposOrdenados();
        $paradas = $this->obtenerParadasOrdenadas();

        $asignacionesHoy = [];
        $itinerariosSemana = [];

        foreach ($grupos as $grupo) {
            $paradaHoy = $this->obtenerParadaDelDia($grupo, $dt);
            $asignacionesHoy[] = [
                'grupo_id'      => $grupo->id,
                'grupo_nombre'  => $grupo->nombre,
                'parada_id'     => $paradaHoy?->id,
                'parada_nombre' => $paradaHoy?->nombre ?? 'N/A',
            ];

            $itinerariosSemana[] = [
                'grupo_id'     => $grupo->id,
                'grupo_nombre' => $grupo->nombre,
                'dias'         => $this->obtenerItinerarioSemanal($grupo, $dt),
            ];
        }

        return [
            'fecha'             => $dt->toDateString(),
            'fecha_formateada'  => $dt->translatedFormat('l, d \d\e F \d\e Y'),
            'lunes_referencia'  => self::LUNES_REFERENCIA,
            'total_paradas'     => $paradas->count(),
            'total_grupos'      => $grupos->count(),
            'paradas'           => $paradas->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre])->values(),
            'grupos'            => $grupos->map(fn ($g) => ['id' => $g->id, 'nombre' => $g->nombre])->values(),
            'asignaciones_hoy'  => $asignacionesHoy,
            'itinerarios_semana'=> $itinerariosSemana,
        ];
    }
}

