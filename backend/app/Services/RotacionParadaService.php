<?php

namespace App\Services;

use App\Models\Grupo;
use App\Models\Lugar;
use Carbon\Carbon;

class RotacionParadaService
{
    /**
     * Orden oficial de las 4 paradas.
     */
    public const PARADAS_ORDEN = [
        'Obelisco',
        'Villa Fátima',
        'Parada 3',
        'Parada 4',
    ];

    /**
     * Orden oficial de los 4 grupos.
     */
    public const GRUPOS_ORDEN = [
        'Grupo A',
        'Grupo B',
        'Grupo C',
        'Grupo D',
    ];

    /**
     * Lunes de referencia para el cálculo de semanas de rotación (03 de Agosto de 2026).
     */
    public const LUNES_REFERENCIA = '2026-08-03';

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

        // Obtener el nombre del grupo
        $nombreGrupo = match (true) {
            $grupo instanceof Grupo => $grupo->nombre,
            is_numeric($grupo) => Grupo::find($grupo)?->nombre,
            default => (string) $grupo,
        };

        if (!$nombreGrupo) {
            return null;
        }

        // 1. Obtener índice del grupo (A=0, B=1, C=2, D=3)
        $grupoIndex = array_search($nombreGrupo, self::GRUPOS_ORDEN);
        if ($grupoIndex === false) {
            // Si el nombre no coincide exactamente (ej. 'Grupo A'), buscar coincidencia parcial
            foreach (self::GRUPOS_ORDEN as $idx => $gNombre) {
                if (stripos($nombreGrupo, $gNombre) !== false) {
                    $grupoIndex = $idx;
                    break;
                }
            }
            if ($grupoIndex === false) $grupoIndex = 0; // Fallback
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

        // 4. Algoritmo de Rotación
        $paradaIndex = ($grupoIndex + $weeksPassed + $dayIndex) % count(self::PARADAS_ORDEN);
        $nombreParada = self::PARADAS_ORDEN[$paradaIndex];

        return Lugar::where('nombre', $nombreParada)->first();
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
}
