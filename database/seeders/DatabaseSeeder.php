<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    private float $startedAt = 0.0;
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->startedAt = microtime(true);
        // Ensure faker locale aligns with seeding config
        Config::set('app.faker_locale', config('seeding.faker_locale', 'pt_BR'));

        $env = App::environment();

        // Always seed base references
        $this->call(BaseSeeder::class);

        if ($env === 'testing') {
            $this->call(TestSeeder::class);
            $this->report();
            return;
        }

        if ($env === 'production') {
            abort('Seeders are disabled in production.');
        }

        // Local/dev/demo
        if ($this->command?->option('class') === DemoSeeder::class || env('SEED_DEMO', false)) {
            $this->call(DemoSeeder::class);
        } else {
            $this->call(DevSeeder::class);
        }

        $this->report();
    }

    private function report(): void
    {
        $rows = [
            'users' => \App\Models\User::count(),
            'clients' => \App\Models\Client::count(),
            'addresses' => \App\Models\Address::count(),
            'products' => \App\Models\Product::count(),
            'product_components' => DB::table('product_components')->count(),
            'leads' => \App\Models\Lead::count(),
            'lead_interactions' => \App\Models\LeadInteraction::count(),
            'orders' => \App\Models\Order::count(),
            'order_items' => \App\Models\OrderItem::count(),
            'opportunities' => \App\Models\Opportunity::count(),
            'opportunity_items' => DB::table('opportunity_items')->count(),
            'sectors' => \App\Models\Sector::count(),
            'machines' => \App\Models\Machine::count(),
            'reason_types' => \App\Models\ReasonType::count(),
            'production_pointings' => \App\Models\ProductionPointing::count(),
            'block_productions' => \App\Models\BlockProduction::count(),
            'block_dispatches' => \App\Models\BlockDispatch::count(),
            'block_dispatch_items' => \App\Models\BlockDispatchItem::count(),
            'molded_productions' => \App\Models\MoldedProduction::count(),
            'molded_dispatches' => \App\Models\MoldedDispatch::count(),
            'inventory_movements' => \App\Models\InventoryMovement::count(),
            'inventory_reservations' => \App\Models\InventoryReservation::count(),
        ];

        $elapsed = microtime(true) - $this->startedAt;
        $this->command?->info(sprintf('Seeding Summary (%.2fs):', $elapsed));
        foreach ($rows as $table => $count) {
            $this->command?->line(sprintf('- %s: %d', $table, $count));
        }

        // Distributions (short):
        $this->command?->line('Distributions:');
        $this->shortDist('users by status', DB::table('users')->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status')->toArray());
        if (Schema::hasTable('clients')) {
            $this->shortDist('clients by status', DB::table('clients')->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status')->toArray());
        }
        $this->shortDist('products by status', DB::table('products')->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status')->toArray());
        if (Schema::hasTable('orders')) {
            $this->shortDist('orders by status', DB::table('orders')->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status')->toArray());
            $this->shortDist('orders by delivery_type', DB::table('orders')->select('delivery_type', DB::raw('count(*) as c'))->groupBy('delivery_type')->pluck('c', 'delivery_type')->toArray());
            $this->shortDist('orders by payment_method', DB::table('orders')->select('payment_method', DB::raw('count(*) as c'))->groupBy('payment_method')->pluck('c', 'payment_method')->toArray());
        }
        if (Schema::hasTable('leads')) {
            $this->shortDist('leads by status', DB::table('leads')->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status')->toArray());
            $this->shortDist('leads by source', DB::table('leads')->select('source', DB::raw('count(*) as c'))->groupBy('source')->pluck('c', 'source')->toArray());
        }
        if (Schema::hasTable('opportunities')) {
            $this->shortDist('opportunities by stage', DB::table('opportunities')->select('stage', DB::raw('count(*) as c'))->groupBy('stage')->pluck('c', 'stage')->toArray());
            $this->shortDist('opportunities by status', DB::table('opportunities')->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status')->toArray());
        }
    }

    private function shortDist(string $label, array $map): void
    {
        if (empty($map)) {
            return;
        }
        $parts = [];
        foreach ($map as $k => $v) {
            if ($k === null || $k === '') {
                $k = 'null';
            }
            $parts[] = sprintf('%s=%d', $k, $v);
        }
        $this->command?->line('  - ' . $label . ': ' . implode(', ', $parts));
    }
}
