<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\User;
use App\Rules\UserHasNoClients;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserHasNoClientsRuleTest extends TestCase
{
  use RefreshDatabase;

  private UserHasNoClients $rule;

  protected function setUp(): void
  {
    parent::setUp();

    $this->rule = new UserHasNoClients();
  }

  public function test_passes_when_user_has_no_clients()
  {
    $user = User::factory()->create();

    $passed = true;
    $failMessage = '';

    $this->rule->validate('user_id', $user->id, function ($message) use (&$passed, &$failMessage) {
      $passed = false;
      $failMessage = $message;
    });

    $this->assertTrue($passed, 'Rule should pass when user has no clients');
    $this->assertEmpty($failMessage, 'No failure message should be set');
  }

  public function test_fails_when_user_has_clients()
  {
    $user = User::factory()->create();
    Client::factory()->create(['created_by_id' => $user->id]);

    $passed = true;
    $failMessage = '';

    $this->rule->validate('user_id', $user->id, function ($message) use (&$passed, &$failMessage) {
      $passed = false;
      $failMessage = $message;
    });

    $this->assertFalse($passed, 'Rule should fail when user has clients');
    $this->assertEquals('Não é possível remover este usuário, pois ele está associado a clientes cadastrados.', $failMessage);
  }

  public function test_passes_when_user_has_clients_but_different_created_by_id()
  {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Client::factory()->create(['created_by_id' => $otherUser->id]);

    $passed = true;
    $failMessage = '';

    $this->rule->validate('user_id', $user->id, function ($message) use (&$passed, &$failMessage) {
      $passed = false;
      $failMessage = $message;
    });

    $this->assertTrue($passed, 'Rule should pass when user has no clients, even if other users have clients');
    $this->assertEmpty($failMessage, 'No failure message should be set');
  }

  public function test_fails_when_user_has_multiple_clients()
  {
    $user = User::factory()->create();
    Client::factory()->create(['created_by_id' => $user->id]);
    Client::factory()->create(['created_by_id' => $user->id]);
    Client::factory()->create(['created_by_id' => $user->id]);

    $passed = true;
    $failMessage = '';

    $this->rule->validate('user_id', $user->id, function ($message) use (&$passed, &$failMessage) {
      $passed = false;
      $failMessage = $message;
    });

    $this->assertFalse($passed, 'Rule should fail when user has multiple clients');
    $this->assertEquals('Não é possível remover este usuário, pois ele está associado a clientes cadastrados.', $failMessage);
  }

  public function test_passes_with_non_existent_user_id()
  {
    $nonExistentUserId = 99999;

    $passed = true;
    $failMessage = '';

    $this->rule->validate('user_id', $nonExistentUserId, function ($message) use (&$passed, &$failMessage) {
      $passed = false;
      $failMessage = $message;
    });

    $this->assertTrue($passed, 'Rule should pass with non-existent user ID');
    $this->assertEmpty($failMessage, 'No failure message should be set');
  }

  public function test_passes_with_null_value()
  {
    $passed = true;
    $failMessage = '';

    $this->rule->validate('user_id', null, function ($message) use (&$passed, &$failMessage) {
      $passed = false;
      $failMessage = $message;
    });

    $this->assertTrue($passed, 'Rule should pass with null value');
    $this->assertEmpty($failMessage, 'No failure message should be set');
  }

  public function test_set_data_method_returns_self()
  {
    $data = ['user_id' => 1, 'name' => 'test'];
    $result = $this->rule->setData($data);

    $this->assertSame($this->rule, $result, 'setData should return self for method chaining');
  }

  public function test_set_data_stores_data_correctly()
  {
    $data = ['user_id' => 1, 'name' => 'test'];
    $this->rule->setData($data);

    // Since data is protected, we can't directly test it, but we can verify the rule still works
    $user = User::factory()->create();

    $passed = true;
    $failMessage = '';

    $this->rule->validate('user_id', $user->id, function ($message) use (&$passed, &$failMessage) {
      $passed = false;
      $failMessage = $message;
    });

    $this->assertTrue($passed, 'Rule should still work after setData is called');
  }

  public function test_rule_implements_required_interfaces()
  {
    $this->assertInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class, $this->rule);
    $this->assertInstanceOf(\Illuminate\Contracts\Validation\DataAwareRule::class, $this->rule);
  }

  public function test_validate_method_signature()
  {
    $reflection = new \ReflectionMethod($this->rule, 'validate');
    $parameters = $reflection->getParameters();

    $this->assertCount(3, $parameters);
    $this->assertEquals('attribute', $parameters[0]->getName());
    $this->assertEquals('value', $parameters[1]->getName());
    $this->assertEquals('fail', $parameters[2]->getName());
  }

  public function test_set_data_method_signature()
  {
    $reflection = new \ReflectionMethod($this->rule, 'setData');
    $parameters = $reflection->getParameters();

    $this->assertCount(1, $parameters);
    $this->assertEquals('data', $parameters[0]->getName());
  }
}
