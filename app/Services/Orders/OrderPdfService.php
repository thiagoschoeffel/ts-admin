<?php

namespace App\Services\Orders;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class OrderPdfService
{
    public function makeHtml(Order $order): string
    {
        return View::make('pdf.orders.show', compact('order'))->render();
    }

    public function render(Order $order, array $opts = []): string
    {
        $html = $this->makeHtml($order);

        $options = [
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
        ];

        $pdf = Pdf::setOptions($options)
            ->setPaper('a4', $opts['orientation'] ?? 'portrait')
            ->loadHtml($html);

        if (isset($opts['watermark']) && $opts['watermark']) {
            // For watermark, we can add it via CSS or manipulate the PDF
            // For simplicity, we'll add a watermark div in the HTML if needed
        }

        return $pdf->output();
    }
}
