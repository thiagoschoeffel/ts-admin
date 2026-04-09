<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\OrderPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrdersPdfController extends Controller
{
    public function show(Request $request, Order $order, OrderPdfService $service)
    {
        $order->load(['client', 'address', 'items.product']);

        $this->authorize('exportPdf', $order);

        if ($order->items()->count() === 0) {
            return response()->json(['message' => __('order.pdf.empty_items')], 422);
        }

        Log::info('Generating PDF for order', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
        ]);

        try {
            $binary = $service->render($order, [
                'orientation' => 'portrait',
                'watermark' => $order->status === 'canceled' ? 'CANCELADO' : null,
            ]);

            $response = response($binary, 200)->header('Content-Type', 'application/pdf');

            if ($request->boolean('download')) {
                $filename = "pedido_{$order->id}.pdf";
                $response->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('Error generating PDF for order', [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => __('order.pdf.generation_error')], 500);
        }
    }
}
