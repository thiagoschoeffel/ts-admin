<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Tests\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AppServiceProviderTest extends TestCase
{
    public function test_provider_can_be_created()
    {
        $provider = new AppServiceProvider(app());
        $this->assertInstanceOf(AppServiceProvider::class, $provider);
    }

    public function test_provider_extends_service_provider()
    {
        $provider = new AppServiceProvider(app());
        $this->assertInstanceOf(\Illuminate\Support\ServiceProvider::class, $provider);
    }

    public function test_register_method_exists()
    {
        $provider = new AppServiceProvider(app());
        $this->assertTrue(method_exists($provider, 'register'));
    }

    public function test_boot_method_exists()
    {
        $provider = new AppServiceProvider(app());
        $this->assertTrue(method_exists($provider, 'boot'));
    }

    public function test_register_inertia_exception_handlers_method_exists()
    {
        $provider = new AppServiceProvider(app());
        $this->assertTrue(method_exists($provider, 'registerInertiaExceptionHandlers'));
    }

    public function test_register_method_runs_without_error()
    {
        $provider = new AppServiceProvider(app());
        $provider->register();
        $this->assertTrue(true);
    }

    public function test_boot_method_runs_without_error()
    {
        $provider = new AppServiceProvider(app());
        $provider->boot();
        $this->assertTrue(true);
    }

    public function test_register_inertia_exception_handlers_registers_renderables_when_inertia_exists()
    {
        $provider = new AppServiceProvider(app());
        $handler = app(ExceptionHandler::class);

        // Get initial renderables count
        $reflection = new \ReflectionProperty($handler, 'renderCallbacks');
        $reflection->setAccessible(true);
        $initialCount = count($reflection->getValue($handler));

        // Call the method
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        // Check that renderables were added
        $finalCount = count($reflection->getValue($handler));
        $this->assertGreaterThan($initialCount, $finalCount);
    }

    public function test_inertia_available_returns_true()
    {
        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'inertiaAvailable');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke($provider));
    }

    public function test_register_inertia_exception_handlers_returns_early_when_inertia_missing()
    {
        $provider = new class(app()) extends AppServiceProvider {
            protected function inertiaAvailable(): bool
            {
                return false;
            }
        };

        $handler = app(ExceptionHandler::class);
        $reflection = new \ReflectionProperty($handler, 'renderCallbacks');
        $reflection->setAccessible(true);
        $initialCount = count($reflection->getValue($handler));

        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $finalCount = count($reflection->getValue($handler));
        $this->assertSame($initialCount, $finalCount);
    }

    public function test_register_inertia_exception_handlers_handles_model_not_found_exception()
    {
        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $handler = app(ExceptionHandler::class);
        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);
        $exception = new \Illuminate\Database\Eloquent\ModelNotFoundException('Model not found');

        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, $exception);

        $this->assertNotNull($response);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
    }

    public function test_register_inertia_exception_handlers_handles_not_found_http_exception()
    {
        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $handler = app(ExceptionHandler::class);
        $request = Request::create('/missing', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);
        $exception = new NotFoundHttpException('Not found');

        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, $exception);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_register_inertia_exception_handlers_handles_access_denied_exception()
    {
        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $handler = app(ExceptionHandler::class);
        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);
        $exception = new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Access denied');

        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, $exception);

        $this->assertNotNull($response);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
    }

    public function test_register_inertia_exception_handlers_handles_http_exception_interface_403()
    {
        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $handler = app(ExceptionHandler::class);
        $request = Request::create('/forbidden', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);
        $exception = new HttpException(403, 'Forbidden');

        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, $exception);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_register_inertia_exception_handlers_handles_authentication_exception()
    {
        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $handler = app(ExceptionHandler::class);
        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);
        $exception = new \Illuminate\Auth\AuthenticationException('Unauthenticated');

        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, $exception);

        $this->assertNotNull($response);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function test_register_inertia_exception_handlers_handles_token_mismatch_exception()
    {
        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $handler = app(ExceptionHandler::class);
        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);
        $exception = new \Illuminate\Session\TokenMismatchException('Token mismatch');

        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, $exception);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_fallback_returns_null_when_debug_true()
    {
        config(['app.debug' => true]);

        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $handler = app(ExceptionHandler::class);
        $request = Request::create('/error', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);
        $exception = new \RuntimeException('boom');

        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, $exception);

        $this->assertNull($response);

        config(['app.debug' => false]);
    }

    public function test_fallback_generic_exception_returns_500_response()
    {
        config(['app.debug' => false]);

        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $handler = app(ExceptionHandler::class);
        $request = Request::create('/error', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);
        $exception = new \Exception('unexpected');

        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, $exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function test_fallback_authentication_exception_redirects_to_login_when_invoked_directly()
    {
        config(['app.debug' => false]);

        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        session()->start();
        $request = Request::create('/auth', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);
        $exception = new \Illuminate\Auth\AuthenticationException('Unauthenticated');

        $handler = app(ExceptionHandler::class);
        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function test_fallback_validation_exception_redirects_back_with_errors()
    {
        config(['app.debug' => false]);

        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $validator = validator(['name' => ''], ['name' => 'required']);
        $exception = new ValidationException($validator);

        session()->start();
        $request = Request::create('/validate', 'POST', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html', 'HTTP_REFERER' => '/previous']);

        $handler = app(ExceptionHandler::class);
        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function test_fallback_not_found_and_model_not_found_return_404()
    {
        config(['app.debug' => false]);
        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $request = Request::create('/missing', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);

        $handler = app(ExceptionHandler::class);
        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);

        $resp1 = $renderMethod->invoke($handler, $request, new NotFoundHttpException());
        $this->assertInstanceOf(Response::class, $resp1);
        $this->assertSame(404, $resp1->getStatusCode());

        $resp2 = $renderMethod->invoke($handler, $request, new \Illuminate\Database\Eloquent\ModelNotFoundException());
        $this->assertInstanceOf(Response::class, $resp2);
        $this->assertSame(404, $resp2->getStatusCode());
    }

    public function test_fallback_access_denied_returns_403()
    {
        config(['app.debug' => false]);
        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'registerInertiaExceptionHandlers');
        $method->setAccessible(true);
        $method->invoke($provider);

        $request = Request::create('/forbidden', 'GET', [], [], [], ['HTTP_X_Inertia' => 'true', 'HTTP_ACCEPT' => 'text/html']);

        $handler = app(ExceptionHandler::class);
        $renderMethod = new ReflectionMethod($handler, 'renderViaCallbacks');
        $renderMethod->setAccessible(true);
        $response = $renderMethod->invoke($handler, $request, new AccessDeniedHttpException());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(403, $response->getStatusCode());
    }
}
