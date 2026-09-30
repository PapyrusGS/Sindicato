<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Usuario;

class NotificacionService
{
    /**
     * Crear y persistir una notificación para un usuario específico.
     */
    public function crearNotificacion(int $usuarioId, string $titulo, string $mensaje, string $tipo = 'INFO', array $data = []): Notificacion
    {
        return Notificacion::create([
            'usuario_id' => $usuarioId,
            'titulo'     => $titulo,
            'mensaje'    => $mensaje,
            'tipo'       => $tipo,
            'data'       => !empty($data) ? $data : null,
            'leido'      => false,
        ]);
    }

    /**
     * Listar notificaciones del usuario autenticado con conteo de no leídas.
     */
    public function listarNotificaciones(Usuario $usuario, int $limit = 30): array
    {
        $notificaciones = Notificacion::where('usuario_id', $usuario->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $noLeidasCount = Notificacion::where('usuario_id', $usuario->id)
            ->where('leido', false)
            ->count();

        return [
            'notificaciones'  => $notificaciones,
            'no_leidas_count' => $noLeidasCount,
        ];
    }

    /**
     * Marcar una notificación como leída.
     */
    public function marcarLeida(int $notificacionId, Usuario $usuario): bool
    {
        $notif = Notificacion::where('id', $notificacionId)
            ->where('usuario_id', $usuario->id)
            ->first();

        if ($notif) {
            $notif->update(['leido' => true]);
            return true;
        }

        return false;
    }

    /**
     * Marcar todas las notificaciones del usuario como leídas.
     */
    public function marcarTodasLeidas(Usuario $usuario): int
    {
        return Notificacion::where('usuario_id', $usuario->id)
            ->where('leido', false)
            ->update(['leido' => true]);
    }
}
