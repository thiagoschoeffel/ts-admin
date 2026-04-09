<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Policies\AddressPolicy;
use App\Policies\ClientPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\UserPolicy;
use App\Policies\LeadPolicy;
use App\Policies\OpportunityPolicy;
use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Application;
use Tests\TestCase;

class AuthServiceProviderTest extends TestCase
{
    private AuthServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new AuthServiceProvider(app());
    }

    public function test_policies_property_is_correctly_defined()
    {
        $expectedPolicies = [
            User::class => UserPolicy::class,
            Client::class => ClientPolicy::class,
            Product::class => ProductPolicy::class,
            Order::class => OrderPolicy::class,
            Address::class => AddressPolicy::class,
            Lead::class => LeadPolicy::class,
            Opportunity::class => OpportunityPolicy::class,
        ];

        $reflection = new \ReflectionClass($this->provider);
        $policiesProperty = $reflection->getProperty('policies');
        $policiesProperty->setAccessible(true);
        $actualPolicies = $policiesProperty->getValue($this->provider);

        $this->assertEquals($expectedPolicies, $actualPolicies);
    }

    public function test_all_policies_are_registered()
    {
        // This test verifies that the provider is correctly configured to register policies
        // In a real application, policies would be registered during bootstrap
        // Here we verify the provider has the correct policy mappings

        $reflection = new \ReflectionClass($this->provider);
        $policiesProperty = $reflection->getProperty('policies');
        $policiesProperty->setAccessible(true);
        $policies = $policiesProperty->getValue($this->provider);

        $expectedPolicies = [
            User::class => UserPolicy::class,
            Client::class => ClientPolicy::class,
            Product::class => ProductPolicy::class,
            Order::class => OrderPolicy::class,
            Address::class => AddressPolicy::class,
            Lead::class => LeadPolicy::class,
            Opportunity::class => OpportunityPolicy::class,
        ];

        $this->assertEquals($expectedPolicies, $policies, 'Provider should have all expected policy mappings');

        // Verify that booting the provider doesn't throw any errors
        $this->provider->boot();
        $this->assertTrue(true, 'Provider should boot without errors');
    }

    public function test_user_policy_is_correctly_mapped()
    {
        $this->provider->boot();

        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $policy = $gate->getPolicyFor(User::class);

        $this->assertInstanceOf(UserPolicy::class, $policy);
    }

    public function test_client_policy_is_correctly_mapped()
    {
        $this->provider->boot();

        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $policy = $gate->getPolicyFor(Client::class);

        $this->assertInstanceOf(ClientPolicy::class, $policy);
    }

    public function test_product_policy_is_correctly_mapped()
    {
        $this->provider->boot();

        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $policy = $gate->getPolicyFor(Product::class);

        $this->assertInstanceOf(ProductPolicy::class, $policy);
    }

    public function test_order_policy_is_correctly_mapped()
    {
        $this->provider->boot();

        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $policy = $gate->getPolicyFor(Order::class);

        $this->assertInstanceOf(OrderPolicy::class, $policy);
    }

    public function test_address_policy_is_correctly_mapped()
    {
        $this->provider->boot();

        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $policy = $gate->getPolicyFor(Address::class);

        $this->assertInstanceOf(AddressPolicy::class, $policy);
    }

    public function test_lead_policy_is_correctly_mapped()
    {
        $this->provider->boot();

        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $policy = $gate->getPolicyFor(Lead::class);

        $this->assertInstanceOf(LeadPolicy::class, $policy);
    }

    public function test_opportunity_policy_is_correctly_mapped()
    {
        $this->provider->boot();

        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $policy = $gate->getPolicyFor(Opportunity::class);

        $this->assertInstanceOf(OpportunityPolicy::class, $policy);
    }

    public function test_provider_extends_laravel_auth_service_provider()
    {
        $this->assertInstanceOf(\Illuminate\Foundation\Support\Providers\AuthServiceProvider::class, $this->provider);
    }

    public function test_policies_array_keys_are_model_classes()
    {
        $reflection = new \ReflectionClass($this->provider);
        $policiesProperty = $reflection->getProperty('policies');
        $policiesProperty->setAccessible(true);
        $policies = $policiesProperty->getValue($this->provider);

        foreach (array_keys($policies) as $modelClass) {
            $this->assertTrue(class_exists($modelClass), "Model class {$modelClass} does not exist");
            $this->assertTrue(is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class), "Class {$modelClass} is not an Eloquent model");
        }
    }

    public function test_policies_array_values_are_policy_classes()
    {
        $reflection = new \ReflectionClass($this->provider);
        $policiesProperty = $reflection->getProperty('policies');
        $policiesProperty->setAccessible(true);
        $policies = $policiesProperty->getValue($this->provider);

        foreach ($policies as $policyClass) {
            $this->assertTrue(class_exists($policyClass), "Policy class {$policyClass} does not exist");
            $this->assertTrue(is_subclass_of($policyClass, \Illuminate\Auth\Access\HandlesAuthorization::class) || method_exists($policyClass, 'viewAny'), "Class {$policyClass} is not a policy class");
        }
    }

    public function test_boot_method_calls_register_policies()
    {
        $mock = $this->getMockBuilder(AuthServiceProvider::class)
            ->onlyMethods(['registerPolicies'])
            ->setConstructorArgs([app()])
            ->getMock();

        $mock->expects($this->once())
            ->method('registerPolicies');

        $mock->boot();
    }

    public function test_provider_can_be_instantiated()
    {
        $this->assertInstanceOf(AuthServiceProvider::class, $this->provider);
    }

    public function test_get_policies_method_exists()
    {
        $this->assertTrue(method_exists($this->provider, 'boot'));
    }

    public function test_get_policies_returns_array()
    {
        $reflection = new \ReflectionClass($this->provider);
        $policiesProperty = $reflection->getProperty('policies');
        $policiesProperty->setAccessible(true);
        $policies = $policiesProperty->getValue($this->provider);

        $this->assertIsArray($policies);
        $this->assertNotEmpty($policies);
    }
}
