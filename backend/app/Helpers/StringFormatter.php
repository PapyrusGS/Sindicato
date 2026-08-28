<?php

namespace App\Helpers;

class StringFormatter
{
    /**
     * Convierte a formato Nombre Propio / Title Case (Primera letra mayúscula, resto minúscula).
     * Soporta palabras compuestas y caracteres con tildes/ñ (ej. "JUAN CARLOS PÉREZ" -> "Juan Carlos Pérez").
     */
    public static function titleCase(?string $value): ?string
    {
        if (is_null($value) || trim($value) === '') {
            return null;
        }

        $clean = preg_replace('/\s+/', ' ', trim($value));
        return mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Limpia y formatea placas de vehículos a mayúsculas sin espacios extra.
     */
    public static function formatPlaca(?string $placa): ?string
    {
        if (is_null($placa) || trim($placa) === '') {
            return null;
        }
        return strtoupper(preg_replace('/\s+/', '', trim($placa)));
    }
}
