<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Restringe una ruta a uno o más roles.
 *
 *   Route::middleware('rol:Administrador,Vendedor')->group(...)
 */
class Rol
{
    public const ROLES = [
        'Administrador' => 1,
        'Vendedor' => 2,
        'Comprador' => 3,
    ];

    public function handle($request, Closure $next, string ...$roles)
    {
        $permitidos = array_map(fn ($rol) => self::ROLES[$rol] ?? null, $roles);
        $usuario = $request->user();

        if (! $usuario || ! in_array((int) $usuario->idrol, $permitidos, true)) {
            abort(403, 'No tenés permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
