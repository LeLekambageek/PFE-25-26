<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->estExpire()) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Ce compte a expiré. Contactez l\'administration.',
            ], 401);
        }

        if ($user && $user->accesPasEncoreActif()) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Ce compte n\'est pas encore actif.',
            ], 401);
        }

        return $next($request);
    }
}
