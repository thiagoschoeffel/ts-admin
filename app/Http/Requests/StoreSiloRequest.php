<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiloRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return $this->user()->can('create', \App\Models\Silo::class);
  }

  public function rules(): array
  {
    return [
      'name' => 'required|string|max:255|unique:silos,name',
      'status' => 'required|in:active,inactive',
    ];
  }
}
