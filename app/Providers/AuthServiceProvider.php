<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Sector;
use App\Models\Almoxarifado;
use App\Models\MachineDowntime;
use App\Models\Operator;
use App\Models\ProductionPointing;
use App\Policies\AddressPolicy;
use App\Policies\ClientPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\UserPolicy;
use App\Policies\LeadPolicy;
use App\Policies\OpportunityPolicy;
use App\Policies\SectorPolicy;
use App\Policies\AlmoxarifadoPolicy;
use App\Policies\MachineDowntimePolicy;
use App\Policies\OperatorPolicy;
use App\Policies\ProductionPointingPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Client::class => ClientPolicy::class,
        Product::class => ProductPolicy::class,
        Order::class => OrderPolicy::class,
        Address::class => AddressPolicy::class,
        Lead::class => LeadPolicy::class,
        Opportunity::class => OpportunityPolicy::class,
        Sector::class => SectorPolicy::class,
        Almoxarifado::class => AlmoxarifadoPolicy::class,
        MachineDowntime::class => MachineDowntimePolicy::class,
        Operator::class => OperatorPolicy::class,
        ProductionPointing::class => ProductionPointingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
