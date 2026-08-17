<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Manejar una solicitud entrante.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        // Si el usuario es Administrador o posee alguno de los roles requeridos
        $tieneAcceso = false;
        foreach ($roles as $rol) {
            if ($user->tieneRol($rol) || $user->tieneRol('Administrador')) {
                $tieneAcceso = true;
                break;
            }
        }

        if (!$tieneAcceso) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Se requiere el rol de ' . implode(' o ', $roles) . ' para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
