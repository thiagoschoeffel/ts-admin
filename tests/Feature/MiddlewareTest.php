<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckPolicy;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticate_redirects_to_login_for_web_requests()
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_authenticate_returns_null_for_json_requests()
    {
        $this->getJson('/admin/dashboard')->assertStatus(401);
    }

    public function test_authenticate_redirect_to_returns_login_for_web()
    {
        $middleware = app(Authenticate::class);
        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('redirectTo');
        $method->setAccessible(true);

        $request = Request::create('/test');
        $request->headers->set('Accept', 'text/html');

        $result = $method->invoke($middleware, $request);
        $this->assertEquals(route('login'), $result);
    }

    public function test_authenticate_redirect_to_returns_null_for_json()
    {
        $middleware = app(Authenticate::class);
        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('redirectTo');
        $method->setAccessible(true);

        $request = Request::create('/test');
        $request->headers->set('Accept', 'application/json');

        $result = $method->invoke($middleware, $request);
        $this->assertNull($result);
    }

    public function test_redirect_if_authenticated_redirects_guests_to_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/login')->assertRedirect('/admin/dashboard');
    }

    public function test_redirect_if_authenticated_allows_guests()
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_ensure_user_is_admin_allows_admins()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $middleware = new EnsureUserIsAdmin();
        $request = Request::create('/test');
        $next = function ($req) {
            return response('ok');
        };

        $response = $middleware->handle($request, $next);
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_ensure_user_is_admin_denies_non_admins()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $middleware = new EnsureUserIsAdmin();
        $request = Request::create('/test');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Acesso restrito a administradores.');

        $middleware->handle($request, function () {});
    }

    public function test_ensure_user_is_admin_denies_guests()
    {
        $middleware = new EnsureUserIsAdmin();
        $request = Request::create('/test');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Acesso restrito a administradores.');

        $middleware->handle($request, function () {});
    }

    public function test_check_policy_allows_with_model_permission()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $middleware = new CheckPolicy();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        $next = function ($req) {
            return response('ok');
        };

        $response = $middleware->handle($request, $next, 'view', 'User');
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_check_policy_denies_with_model_permission()
    {
        $user = User::factory()->create(['role' => 'user', 'permissions' => []]);
        $middleware = new CheckPolicy();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Acesso negado. Você não tem permissão para realizar esta ação.');

        $middleware->handle($request, function () {}, 'view', 'User');
    }

    public function test_check_policy_allows_with_route_model_permission()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $model = User::factory()->create();
        $middleware = new CheckPolicy();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        $route = Mockery::mock();
        $route->shouldReceive('parameters')->andReturn([$model]);
        $request->shouldReceive('route')->andReturn($route);
        $next = function ($req) {
            return response('ok');
        };

        $response = $middleware->handle($request, $next, 'view');
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_check_policy_denies_with_route_model_permission()
    {
        $user = User::factory()->create(['role' => 'user', 'permissions' => []]);
        $model = User::factory()->create();
        $middleware = new CheckPolicy();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        $route = Mockery::mock();
        $route->shouldReceive('parameters')->andReturn([$model]);
        $request->shouldReceive('route')->andReturn($route);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Acesso negado. Você não tem permissão para realizar esta ação.');

        $middleware->handle($request, function () {}, 'view');
    }

    public function test_check_policy_denies_without_user()
    {
        $middleware = new CheckPolicy();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn(null);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function () {}, 'view');
    }

    public function test_check_policy_allows_without_route_model()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $middleware = new CheckPolicy();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        $route = Mockery::mock();
        $route->shouldReceive('parameters')->andReturn([]); // no objects
        $request->shouldReceive('route')->andReturn($route);
        $next = function ($req) {
            return response('ok');
        };

        $response = $middleware->handle($request, $next, 'view');
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_check_policy_allows_with_non_object_route_parameters()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $middleware = new CheckPolicy();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        $route = Mockery::mock();
        $route->shouldReceive('parameters')->andReturn(['id' => 1, 'name' => 'test']); // non-objects
        $request->shouldReceive('route')->andReturn($route);
        $next = function ($req) {
            return response('ok');
        };

        $response = $middleware->handle($request, $next, 'view');
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_handle_inertia_requests_shares_data_with_user()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $middleware = new HandleInertiaRequests(app(), 'app');
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);
        $request->shouldReceive('hasSession')->andReturn(true);
        $session = Mockery::mock();
        $session->shouldReceive('get')->andReturn(null);
        $session->shouldReceive('has')->andReturn(false);
        $request->shouldReceive('session')->andReturn($session);

        $shared = $middleware->share($request);

        $this->assertArrayHasKey('auth', $shared);
        $this->assertArrayHasKey('user', $shared['auth']);
        $this->assertEquals($user->id, $shared['auth']['user']['id']);
    }

    public function test_handle_inertia_requests_shares_data_without_user()
    {
        $middleware = new HandleInertiaRequests(app(), 'app');
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn(null);
        $request->shouldReceive('hasSession')->andReturn(true);
        $session = Mockery::mock();
        $session->shouldReceive('get')->andReturn(null);
        $session->shouldReceive('has')->andReturn(false);
        $request->shouldReceive('session')->andReturn($session);

        $shared = $middleware->share($request);

        $this->assertArrayHasKey('auth', $shared);
        $this->assertNull($shared['auth']['user']);
    }
}
