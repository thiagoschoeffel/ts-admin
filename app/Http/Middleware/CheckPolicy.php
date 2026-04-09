<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $policy, string $model = null): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // Se um modelo específico for fornecido (ex: "viewAny:User")
        if ($model) {
            $modelClass = "App\\Models\\{$model}";
            if (!$user->can($policy, $modelClass)) {
                abort(403, 'Acesso negado. Você não tem permissão para realizar esta ação.');
            }
        } else {
            // Para policies que requerem uma instância específica
            $routeModel = $this->getRouteModel($request);
            if ($routeModel && !$user->can($policy, $routeModel)) {
                abort(403, 'Acesso negado. Você não tem permissão para realizar esta ação.');
            }
        }

        return $next($request);
    }

    /**
     * Get the model instance from route parameters
     */
    private function getRouteModel(Request $request)
    {
        // Tenta encontrar o primeiro parâmetro de rota que seja um modelo
        foreach ($request->route()->parameters() as $parameter) {
            if (is_object($parameter) && method_exists($parameter, 'getKey')) {
                return $parameter;
            }
        }
        return null;
    }
}
