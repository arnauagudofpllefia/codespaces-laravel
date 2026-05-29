<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware para restringir rutas por rol exacto (ej. admin).
 */
class CheckRole
{
    /**
     * Si el usuario no está autenticado o no coincide su rol, retorna 403.
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if (!$request->user() || $request->user()->rol !== $role) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        return $next($request);
    }
}
