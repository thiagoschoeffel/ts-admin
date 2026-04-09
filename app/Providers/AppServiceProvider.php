<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar renderables para exceções HTTP comuns, retornando páginas Inertia
        $this->registerInertiaExceptionHandlers();
    }

    protected function registerInertiaExceptionHandlers(): void
    {
        // Só registra se a classe Inertia existir
        if (!$this->inertiaAvailable()) {
            return;
        }

        // Handler genérico para exceções http
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class; // garantir carregamento

        // Renderable closures para códigos comuns
        $handler = app()->make(ExceptionHandlerContract::class);
        /** @var \Illuminate\Foundation\Exceptions\Handler $handler */
        $handler->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->header('X-Inertia') || str_contains($request->header('Accept', ''), 'text/html')) {
                // Mesclar props globais do Inertia manualmente
                $globals = app(\App\Http\Middleware\HandleInertiaRequests::class)->share($request);
                return Inertia::render('Errors/404', array_merge($globals, ['url' => $request->getRequestUri()]))->toResponse($request)->setStatusCode(404);
            }
        });

        // Rota Model Binding falhou (ex: Client não existe) -> retornar Errors/404 via Inertia
        $handler->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->header('X-Inertia') || str_contains($request->header('Accept', ''), 'text/html')) {
                return Inertia::render('Errors/404', ['url' => $request->getRequestUri()])->toResponse($request)->setStatusCode(404);
            }
        });

        // Tratar Unauthenticated separadamente: redirecionar ao login (evita transformar em 500)
        $handler->renderable(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            // Para requisições Inertia / HTML, redirecionar ao formulário de login
            if ($request->header('X-Inertia') || str_contains($request->header('Accept', ''), 'text/html')) {
                // Provide a friendly flash message so layouts can show a toast on the login page
                session()->flash('status', __('validation.custom_messages.login_required'));
                session()->flash('flash_id', (string) Str::uuid());
                return redirect()->guest(route('login'));
            }
        });

        $handler->renderable(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Request $request) {
            if ($request->header('X-Inertia') || str_contains($request->header('Accept', ''), 'text/html')) {
                // Flash a toast-friendly error so the client layouts will display it
                session()->flash('error', __('validation.custom_messages.access_denied'));
                session()->flash('flash_id', (string) Str::uuid());
                $globals = app(\App\Http\Middleware\HandleInertiaRequests::class)->share($request);
                return Inertia::render('Errors/403', array_merge($globals, ['url' => $request->getRequestUri()]))->toResponse($request)->setStatusCode(403);
            }
        });

        // Tratar HttpException com status 403 (abort(403) gera HttpExceptionInterface)
        // Mantido após o handler específico de AccessDenied para não sobrepor flashes
        $handler->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) {
            if ($request->header('X-Inertia') || str_contains($request->header('Accept', ''), 'text/html')) {
                if ($e->getStatusCode() === 403) {
                    // Ensure a user-friendly toast is flashed even for generic 403s
                    session()->flash('error', __('validation.custom_messages.access_denied'));
                    session()->flash('flash_id', (string) Str::uuid());
                    $globals = app(\App\Http\Middleware\HandleInertiaRequests::class)->share($request);
                    return Inertia::render('Errors/403', array_merge($globals, ['url' => $request->getRequestUri()]))->toResponse($request)->setStatusCode(403);
                }
            }
        });

        $handler->renderable(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            if ($request->header('X-Inertia') || str_contains($request->header('Accept', ''), 'text/html')) {
                // Session expired: show a toast and the 419 page
                session()->flash('error', __('validation.custom_messages.session_expired'));
                session()->flash('flash_id', (string) Str::uuid());
                $globals = app(\App\Http\Middleware\HandleInertiaRequests::class)->share($request);
                return Inertia::render('Errors/419', array_merge($globals, ['url' => $request->getRequestUri()]))->toResponse($request)->setStatusCode(419);
            }
        });

        $handler->renderable(function (Throwable $e, Request $request) {
            // If we're in debug mode, don't intercept - let Laravel display the Whoops/debug page.
            if (config('app.debug')) {
                return null;
            }

            // Se a exceção for específica, tratá-la aqui para evitar que o handler genérico a transforme em 500
            if ($request->header('X-Inertia') || str_contains($request->header('Accept', ''), 'text/html')) {
                // Authentication -> redirecionar para login
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return redirect()->guest(route('login'));
                }

                // Validation exceptions -> return errors (422) or redirect back with errors
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    // Flash validation errors as toast messages
                    if ($request->header('X-Inertia') || str_contains($request->header('Accept', ''), 'text/html')) {
                        // Instead of flashing as toast, redirect back with errors so they appear inline
                        return redirect()->back()->withErrors($e->validator)->withInput();
                    }
                }

                // Model not found / NotFoundHttpException -> mostrar 404 Inertia
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException || $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return Inertia::render('Errors/404', ['url' => $request->getRequestUri()])->toResponse($request)->setStatusCode(404);
                }

                // Access denied -> 403 Inertia
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                    return Inertia::render('Errors/403', ['url' => $request->getRequestUri()])->toResponse($request)->setStatusCode(403);
                }

                // Fallback: 500 genérico
                // Flash a generic error (avoid leaking the raw exception message to users in production)
                session()->flash('error', __('validation.custom_messages.unexpected_error'));
                session()->flash('flash_id', (string) Str::uuid());
                $globals = app(\App\Http\Middleware\HandleInertiaRequests::class)->share($request);
                return Inertia::render('Errors/500', array_merge($globals, ['message' => config('app.debug') ? $e->getMessage() : null, 'url' => $request->getRequestUri()]))->toResponse($request)->setStatusCode(500);
            }
        });
    }

    // Extraído para facilitar testes do caminho de retorno antecipado
    protected function inertiaAvailable(): bool
    {
        return class_exists(Inertia::class);
    }
}
