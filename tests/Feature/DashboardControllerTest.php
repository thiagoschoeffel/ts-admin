<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_returns_stats_and_sales_chart(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertInertia(function ($page) {
            $page->component('Admin/Dashboard')
                ->has('stats')
                ->has('salesChart');
        });
    }

    public function test_dashboard_sales_chart_with_orders(): void
    {
        // Create an order for today
        Order::factory()->create([
            'ordered_at' => now(),
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertInertia(function ($page) {
            $page->component('Admin/Dashboard')
                ->has('salesChart');
        });
    }

    public function test_dashboard_sales_chart_no_orders(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertInertia(function ($page) {
            $page->component('Admin/Dashboard')
                ->has('salesChart');
        });
    }
}
