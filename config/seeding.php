<?php

return [
    // Locale for faker-generated data
    'faker_locale' => env('SEEDER_FAKER_LOCALE', 'pt_BR'),

    // Global seed to enable deterministic seeding in tests
    'seed' => (int) env('SEEDER_SEED', 12345),

    // Weighted distributions used by factories/seeders
    'weights' => [
        'status' => [
            // Generic active/inactive distribution
            'active' => 70,
            'inactive' => 30,
        ],
        'lead_status' => [
            'new' => 40,
            'in_contact' => 30,
            'qualified' => 20,
            'discarded' => 10,
        ],
        'lead_source' => [
            'site' => 35,
            'indicacao' => 35,
            'evento' => 15,
            'manual' => 15,
        ],
        'opportunity_stage' => [
            'new' => 25,
            'contact' => 25,
            'proposal' => 20,
            'negotiation' => 15,
            'won' => 10,
            'lost' => 5,
        ],
        'order_status' => [
            'pending' => 35,
            'confirmed' => 25,
            'shipped' => 10,
            'delivered' => 20,
            'cancelled' => 10,
        ],
        'payment_method' => [
            'cash' => 30,
            'card' => 45,
            'pix' => 25,
        ],
        'delivery_type' => [
            'pickup' => 40,
            'delivery' => 60,
        ],
        'address_status' => [
            'active' => 85,
            'inactive' => 15,
        ],
    ],

    // Default volumes for Dev/Demo seeding. Can be overridden via CLI options.
    'volumes' => [
        'clients' => 50,
        'addresses_per_client' => [1, 2],
        'products' => 80,
        'leads' => 30,
        'lead_interactions_per_lead' => [0, 5],
        'orders' => 40,
        'opportunities' => 50,
        'sectors' => 10,
        'almoxarifados' => 10,
        'machines' => 15,
        'reason_types' => 5,
        'reasons' => 23,
        'machine_downtimes' => 30,
        'operators' => 7,
        'production_pointings' => 10,
        'raw_materials' => 10,
        'silos' => 10,
        'block_types' => 10,
        'mold_types' => 10,
    ],
];
