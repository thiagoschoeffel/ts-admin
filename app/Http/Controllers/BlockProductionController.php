<?php

namespace App\Http\Controllers;

use App\Models\BlockProduction;
use App\Models\BlockType;
use App\Models\ProductionPointing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockProductionController extends Controller
{
    public function index(Request $request, ProductionPointing $productionPointing): JsonResponse
    {
        $this->authorize('view', $productionPointing);

        $query = BlockProduction::query()
            ->where('production_pointing_id', $productionPointing->id)
            ->with(['blockType:id,name,raw_material_percentage', 'operators:id,name', 'silos:id,name'])
            ->orderBy('id');

        if ($sheet = $request->query('sheet_number')) {
            $query->where('sheet_number', (int) $sheet);
        }

        $rows = $query->get();

        $payload = $rows->map(function (BlockProduction $bp, $idx) {
            $bt = $bp->blockType;
            $virginPct = $bt?->raw_material_percentage !== null ? (float) $bt->raw_material_percentage : 0.0;
            $recycledPct = max(0.0, 100.0 - $virginPct);
            $totalKg = (float) $bp->weight;
            $virginKg = $totalKg * ($virginPct / 100.0);
            $recycledKg = $totalKg * ($recycledPct / 100.0);
            $m3 = ($bp->length_mm / 1000.0) * ($bp->width_mm / 1000.0) * ($bp->height_mm / 1000.0);
            $density = $m3 > 0 ? $totalKg / $m3 : null;

            return [
                'id' => $bp->id,
                'seq' => $idx + 1, // UI sequence starting at 1
                'block_type_id' => $bp->block_type_id,
                'started_at' => $bp->started_at?->format('Y-m-d H:i:s'),
                'ended_at' => $bp->ended_at?->format('Y-m-d H:i:s'),
                'sheet_number' => $bp->sheet_number,
                'weight' => $totalKg,
                'virgin_kg' => $virginKg,
                'recycled_kg' => $recycledKg,
                'density' => $density,
                'block_type_name' => $bt?->name,
                'virgin_pct' => $virginPct,
                'recycled_pct' => $recycledPct,
                'length_mm' => $bp->length_mm,
                'width_mm' => $bp->width_mm,
                'height_mm' => $bp->height_mm,
                'm3' => $m3,
                'silo_names' => $bp->silos->pluck('name')->values(),
                'operator_names' => $bp->operators->pluck('name')->values(),
                'silo_ids' => $bp->silos->pluck('id')->values(),
                'operator_ids' => $bp->operators->pluck('id')->values(),
                'is_scrap' => (bool) $bp->is_scrap,
                'dimension_customization_enabled' => (bool) $bp->dimension_customization_enabled,
            ];
        })->values();

        return response()->json(['data' => $payload]);
    }
    public function store(Request $request, ProductionPointing $productionPointing): JsonResponse
    {
        $this->authorize('create', BlockProduction::class);

        $validated = $request->validate([
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date'],
            'sheet_number' => ['required', 'integer', 'min:1'],
            'weight' => ['required', 'numeric', 'min:0.01'],
            'block_type_id' => ['required', 'exists:block_types,id'],
            'length_mm' => ['nullable', 'integer', 'min:1'],
            'width_mm' => ['nullable', 'integer', 'min:1'],
            'height_mm' => ['required', 'integer', 'min:1'],
            'is_scrap' => ['nullable', 'boolean'],
            'dimension_customization_enabled' => ['nullable', 'boolean'],
            'operator_ids' => ['array'],
            'operator_ids.*' => ['integer', 'exists:operators,id'],
            'silo_ids' => ['array'],
            'silo_ids.*' => ['integer', 'exists:silos,id'],
        ]);

        $length = $validated['length_mm'] ?? 4060;
        $width = $validated['width_mm'] ?? 1020;

        $bp = BlockProduction::create([
            'production_pointing_id' => $productionPointing->id,
            'block_type_id' => (int) $validated['block_type_id'],
            'started_at' => $validated['started_at'],
            'ended_at' => $validated['ended_at'],
            'sheet_number' => (int) $validated['sheet_number'],
            'weight' => (float) $validated['weight'],
            'length_mm' => (int) $length,
            'width_mm' => (int) $width,
            'height_mm' => (int) $validated['height_mm'],
            'is_scrap' => (bool) ($validated['is_scrap'] ?? false),
            'dimension_customization_enabled' => (bool) ($validated['dimension_customization_enabled'] ?? false),
            'created_by_id' => Auth::id(),
            'updated_by_id' => Auth::id(),
        ]);

        $bp->operators()->sync($validated['operator_ids'] ?? []);
        $bp->silos()->sync($validated['silo_ids'] ?? []);

        // Movimenta estoque: consumo de MP e entrada de bloco
        app(\App\Services\Inventory\InventoryService::class)->syncBlockProduction($bp);

        // Build response payload with computed values
        $bp->load(['blockType:id,name,raw_material_percentage', 'operators:id,name', 'silos:id,name']);
        $bt = $bp->blockType;
        $virginPct = $bt?->raw_material_percentage !== null ? (float) $bt->raw_material_percentage : 0.0;
        $recycledPct = max(0.0, 100.0 - $virginPct);
        $totalKg = (float) $bp->weight;
        $virginKg = $totalKg * ($virginPct / 100.0);
        $recycledKg = $totalKg * ($recycledPct / 100.0);
        $m3 = ($bp->length_mm / 1000.0) * ($bp->width_mm / 1000.0) * ($bp->height_mm / 1000.0);
        $density = $m3 > 0 ? $totalKg / $m3 : null;

        return response()->json([
            'blockProduction' => [
                'id' => $bp->id,
                'seq' => $bp->id, // frontend uses incremental independent sequence; this is a placeholder
                'started_at' => $bp->started_at?->format('Y-m-d H:i:s'),
                'ended_at' => $bp->ended_at?->format('Y-m-d H:i:s'),
                'sheet_number' => $bp->sheet_number,
                'weight' => $totalKg,
                'virgin_kg' => $virginKg,
                'recycled_kg' => $recycledKg,
                'density' => $density,
                'block_type_name' => $bt?->name,
                'virgin_pct' => $virginPct,
                'recycled_pct' => $recycledPct,
                'length_mm' => $bp->length_mm,
                'width_mm' => $bp->width_mm,
                'height_mm' => $bp->height_mm,
                'm3' => $m3,
                'silo_names' => $bp->silos->pluck('name')->values(),
                'operator_names' => $bp->operators->pluck('name')->values(),
            ],
        ]);
    }

    public function destroy(ProductionPointing $productionPointing, BlockProduction $blockProduction): JsonResponse
    {
        $this->authorize('delete', $blockProduction);
        if ($blockProduction->production_pointing_id !== $productionPointing->id) {
            abort(404);
        }
        // Apagar movimentos associados antes de excluir
        \App\Models\InventoryMovement::query()
            ->where('reference_type', \App\Models\BlockProduction::class)
            ->where('reference_id', $blockProduction->id)
            ->delete();
        $blockProduction->delete();
        return response()->json(['status' => 'ok']);
    }

    public function update(Request $request, ProductionPointing $productionPointing, BlockProduction $blockProduction): JsonResponse
    {
        $this->authorize('update', $blockProduction);
        if ($blockProduction->production_pointing_id !== $productionPointing->id) {
            abort(404);
        }

        $validated = $request->validate([
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date'],
            'sheet_number' => ['required', 'integer', 'min:1'],
            'weight' => ['required', 'numeric', 'min:0.01'],
            'block_type_id' => ['required', 'exists:block_types,id'],
            'length_mm' => ['nullable', 'integer', 'min:1'],
            'width_mm' => ['nullable', 'integer', 'min:1'],
            'height_mm' => ['required', 'integer', 'min:1'],
            'is_scrap' => ['nullable', 'boolean'],
            'dimension_customization_enabled' => ['nullable', 'boolean'],
            'operator_ids' => ['array'],
            'operator_ids.*' => ['integer', 'exists:operators,id'],
            'silo_ids' => ['array'],
            'silo_ids.*' => ['integer', 'exists:silos,id'],
        ]);

        $length = $validated['length_mm'] ?? 4060;
        $width = $validated['width_mm'] ?? 1020;

        $blockProduction->update([
            'block_type_id' => (int) $validated['block_type_id'],
            'started_at' => $validated['started_at'],
            'ended_at' => $validated['ended_at'],
            'sheet_number' => (int) $validated['sheet_number'],
            'weight' => (float) $validated['weight'],
            'length_mm' => (int) $length,
            'width_mm' => (int) $width,
            'height_mm' => (int) $validated['height_mm'],
            'is_scrap' => (bool) ($validated['is_scrap'] ?? false),
            'dimension_customization_enabled' => (bool) ($validated['dimension_customization_enabled'] ?? false),
            'updated_by_id' => Auth::id(),
        ]);

        $blockProduction->operators()->sync($validated['operator_ids'] ?? []);
        $blockProduction->silos()->sync($validated['silo_ids'] ?? []);

        // Sincroniza movimentos de estoque após atualização
        app(\App\Services\Inventory\InventoryService::class)->syncBlockProduction($blockProduction);

        $blockProduction->load(['blockType:id,name,raw_material_percentage', 'operators:id,name', 'silos:id,name']);
        $bt = $blockProduction->blockType;
        $virginPct = $bt?->raw_material_percentage !== null ? (float) $bt->raw_material_percentage : 0.0;
        $recycledPct = max(0.0, 100.0 - $virginPct);
        $totalKg = (float) $blockProduction->weight;
        $virginKg = $totalKg * ($virginPct / 100.0);
        $recycledKg = $totalKg * ($recycledPct / 100.0);
        $m3 = ($blockProduction->length_mm / 1000.0) * ($blockProduction->width_mm / 1000.0) * ($blockProduction->height_mm / 1000.0);
        $density = $m3 > 0 ? $totalKg / $m3 : null;

        return response()->json([
            'blockProduction' => [
                'id' => $blockProduction->id,
                'started_at' => $blockProduction->started_at?->format('Y-m-d H:i:s'),
                'ended_at' => $blockProduction->ended_at?->format('Y-m-d H:i:s'),
                'sheet_number' => $blockProduction->sheet_number,
                'weight' => $totalKg,
                'virgin_kg' => $virginKg,
                'recycled_kg' => $recycledKg,
                'density' => $density,
                'block_type_name' => $bt?->name,
                'virgin_pct' => $virginPct,
                'recycled_pct' => $recycledPct,
                'length_mm' => $blockProduction->length_mm,
                'width_mm' => $blockProduction->width_mm,
                'height_mm' => $blockProduction->height_mm,
                'm3' => $m3,
                'silo_names' => $blockProduction->silos->pluck('name')->values(),
                'operator_names' => $blockProduction->operators->pluck('name')->values(),
                'silo_ids' => $blockProduction->silos->pluck('id')->values(),
                'operator_ids' => $blockProduction->operators->pluck('id')->values(),
                'block_type_id' => $blockProduction->block_type_id,
                'is_scrap' => (bool) $blockProduction->is_scrap,
            ],
        ]);
    }
}
