<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Similar to Dev seeding, but volumes can be overridden via --qtd
        Schema::disableForeignKeyConstraints();
        foreach ([
            'inventory_movements', 'inventory_reservations',
            'block_dispatch_items', 'block_dispatches',
            'molded_production_operator','molded_production_silo','molded_productions',
            'block_production_operator','block_production_silo','block_productions',
            'order_items', 'orders',
            'product_components', 'products',
            'production_pointing_operator', 'production_pointing_silo', 'production_pointings',
            'raw_materials', 'operators', 'silos',
            'addresses', 'clients',
            'lead_interactions', 'opportunity_items', 'opportunities', 'leads',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();

        DB::transaction(function () {
            $this->call(UserSeeder::class);
            $this->call(ClientSeeder::class);
            $this->call(ProductSeeder::class);
            $this->call(LeadSeeder::class);
            $this->call(LeadInteractionSeeder::class);
            $this->call(OrderSeeder::class);
            $this->call(OpportunitySeeder::class);
        });
    }
}
