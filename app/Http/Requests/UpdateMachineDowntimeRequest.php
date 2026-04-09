<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMachineDowntimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $downtime = $this->route('machineDowntime');
        return $this->user()->can('update', $downtime ?? \App\Models\MachineDowntime::class);
    }

    public function rules(): array
    {
        return [
            'machine_id' => ['required', 'exists:machines,id'],
            'reason_id' => ['required', 'exists:reasons,id'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after_or_equal:started_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}

