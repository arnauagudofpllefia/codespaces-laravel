<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Administra la bandeja de notificaciones in-app del usuario autenticado.
 */
class NotificacionesController extends Controller
{
    /**
     * Lista notificaciones activas (no expiradas), con opción de filtrar no leídas.
     */
    public function index(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'unread_only' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        // Priorizamos no leídas arriba para mejorar la UX del inbox.
        $query = Notificacion::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByRaw('read_at is null desc')
            ->orderByDesc('created_at');

        if (($datos['unread_only'] ?? false) === true) {
            $query->whereNull('read_at');
        }

        $notificaciones = $query
            ->limit((int) ($datos['limit'] ?? 50))
            ->get();

        $unreadCount = Notificacion::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        return response()->json([
            'data' => $notificaciones,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Devuelve únicamente el contador de no leídas para badges del frontend.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $unreadCount = Notificacion::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Marca una notificación como leída verificando propiedad del recurso.
     */
    public function markAsRead(Request $request, Notificacion $notificacion): JsonResponse
    {
        if ((int) $notificacion->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta notificacion.',
            ], 403);
        }

        if ($notificacion->read_at === null) {
            $notificacion->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return response()->json([
            'message' => 'Notificacion marcada como leida.',
            'data' => $notificacion->fresh(),
        ]);
    }

    /**
     * Marca como leídas todas las notificaciones pendientes del usuario.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = Notificacion::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Notificaciones marcadas como leidas.',
            'updated' => $updated,
        ]);
    }
}
