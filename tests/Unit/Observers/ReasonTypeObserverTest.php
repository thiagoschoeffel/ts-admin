<?php

namespace Tests\Unit\Observers;

use App\Models\ReasonType;
use App\Models\User;
use App\Observers\ReasonTypeObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ReasonTypeObserverTest extends TestCase
{
    use RefreshDatabase;

    private ReasonTypeObserver $observer;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->observer = new ReasonTypeObserver();
        $this->user = User::factory()->create();
    }

    public function test_created_logs_reason_type_creation()
    {
        Auth::login($this->user);

        $reasonType = new ReasonType([
            'name' => 'Novo Tipo de Motivo',
            'status' => 'active',
        ]);
        $reasonType->id = 1; // Simulate saved model

        Log::shouldReceive('info')
            ->once()
            ->with('Tipo de motivo criado', [
                'reason_type_id' => 1,
                'name' => 'Novo Tipo de Motivo',
                'status' => 'active',
                'user_id' => $this->user->id,
            ]);
    }

    public function test_created_logs_reason_type_creation_without_authenticated_user()
    {
        Auth::logout();

        $reasonType = new ReasonType([
            'name' => 'Novo Tipo de Motivo',
            'status' => 'active',
        ]);
        $reasonType->id = 1; // Simulate saved model

        Log::shouldReceive('info')
            ->once()
            ->with('Tipo de motivo criado', [
                'reason_type_id' => 1,
                'name' => 'Novo Tipo de Motivo',
                'status' => 'active',
                'user_id' => null,
            ]);
    }

    public function test_updated_logs_reason_type_changes()
    {
        Auth::login($this->user);

        $reasonType = new ReasonType([
            'name' => 'Tipo Original',
            'status' => 'active',
        ]);
        $reasonType->id = 1;
        $reasonType->name = 'Tipo Atualizado';
        $reasonType->status = 'inactive';

        // Simulate changes
        $reasonType->setChanges([
            'name' => 'Tipo Atualizado',
            'status' => 'inactive',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Tipo de motivo atualizado', [
                'reason_type_id' => 1,
                'changes' => [
                    'name' => 'Tipo Atualizado',
                    'status' => 'inactive',
                ],
                'user_id' => $this->user->id,
            ]);
    }

    public function test_updated_logs_reason_type_changes_without_authenticated_user()
    {
        Auth::logout();

        $reasonType = new ReasonType([
            'name' => 'Tipo Original',
            'status' => 'active',
        ]);
        $reasonType->id = 1;
        $reasonType->name = 'Tipo Atualizado';

        // Simulate changes
        $reasonType->setChanges([
            'name' => 'Tipo Atualizado',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Tipo de motivo atualizado', [
                'reason_type_id' => 1,
                'changes' => [
                    'name' => 'Tipo Atualizado',
                ],
                'user_id' => null,
            ]);
    }

    public function test_deleted_logs_reason_type_deletion()
    {
        Auth::login($this->user);

        $reasonType = new ReasonType([
            'name' => 'Tipo a Ser Excluído',
            'status' => 'active',
        ]);
        $reasonType->id = 1;

        Log::shouldReceive('info')
            ->once()
            ->with('Tipo de motivo excluído', [
                'reason_type_id' => 1,
                'name' => 'Tipo a Ser Excluído',
                'status' => 'active',
                'user_id' => $this->user->id,
            ]);
    }

    public function test_deleted_logs_reason_type_deletion_without_authenticated_user()
    {
        Auth::logout();

        $reasonType = new ReasonType([
            'name' => 'Tipo a Ser Excluído',
            'status' => 'active',
        ]);
        $reasonType->id = 1;

        Log::shouldReceive('info')
            ->once()
            ->with('Tipo de motivo excluído', [
                'reason_type_id' => 1,
                'name' => 'Tipo a Ser Excluído',
                'status' => 'active',
                'user_id' => null,
            ]);
    }

    public function test_created_method_exists()
    {
        $this->assertTrue(method_exists($this->observer, 'created'));
    }

    public function test_updated_method_exists()
    {
        $this->assertTrue(method_exists($this->observer, 'updated'));
    }

    public function test_deleted_method_exists()
    {
        $this->assertTrue(method_exists($this->observer, 'deleted'));
    }
}
