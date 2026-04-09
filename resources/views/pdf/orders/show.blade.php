<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #{{ $order->id }}</title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .info {
            margin-bottom: 20px;
        }
        .info table {
            width: 100%;
            border-collapse: collapse;
        }
        .info td {
            padding: 2px 5px;
        }
        .items {
            margin-bottom: 20px;
        }
        .items table {
            width: 100%;
            border-collapse: collapse;
        }
        .items th, .items td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        .items th {
            background-color: #f0f0f0;
        }
        .items .quantity, .items .price, .items .subtotal {
            text-align: right;
        }
        .totals {
            text-align: right;
            margin-bottom: 20px;
        }
        .totals table {
            float: right;
        }
        .totals td {
            padding: 2px 10px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 20px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            color: rgba(255, 0, 0, 0.3);
            z-index: 1000;
            pointer-events: none;
        }
    </style>
</head>
<body>
    @if($order->status === 'canceled')
        <div class="watermark">CANCELADO</div>
    @endif

    <div class="header">
        <h1>Pedido #{{ $order->id }}</h1>
        <p>Gerado em: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="info">
        <table>
            <tr>
                <td><strong>Cliente:</strong> {{ $order->client->name ?? 'N/A' }}</td>
                <td><strong>Data do Pedido:</strong> {{ $order->ordered_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Endereço:</strong> {{ $order->address ? ($order->address->address . ', ' . $order->address->address_number . ' - ' . $order->address->city . '/' . $order->address->state) : 'N/A' }}</td>
                <td><strong>Status:</strong> {{ ucfirst($order->status) }}</td>
            </tr>
            <tr>
                <td><strong>Forma de Pagamento:</strong> {{ ucfirst($order->payment_method) }}</td>
                <td><strong>Tipo de Entrega:</strong> {{ $order->delivery_type === 'pickup' ? 'Retirada' : 'Entrega' }}</td>
            </tr>
        </table>
    </div>

    <div class="items">
        <h3>Itens do Pedido</h3>
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Preço Unitário</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td class="quantity">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                        <td class="price">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                        <td class="subtotal">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals">
        <table>
            <tr>
                <td><strong>Total:</strong></td>
                <td>R$ {{ number_format($order->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Este documento foi gerado automaticamente pelo sistema.</p>
        <p>Para dúvidas, entre em contato conosco.</p>
    </div>
</body>
</html>
