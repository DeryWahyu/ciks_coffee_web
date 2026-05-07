<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan - Ciks Coffee</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 3px solid #3E2723; padding-bottom: 15px; }
        .header h1 { font-size: 20px; color: #3E2723; margin: 0 0 5px; letter-spacing: 2px; text-transform: uppercase; }
        .header p { font-size: 10px; color: #A1887F; margin: 0; }
        .meta { margin-bottom: 15px; }
        .meta table { width: 100%; }
        .meta td { padding: 2px 0; font-size: 10px; }
        .meta .label { color: #A1887F; font-weight: bold; width: 120px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data thead th { background-color: #3E2723; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; padding: 8px 6px; text-align: left; }
        table.data thead th.right { text-align: right; }
        table.data tbody td { padding: 6px; border-bottom: 1px solid #eee; font-size: 9px; }
        table.data tbody td.right { text-align: right; }
        table.data tbody tr:nth-child(even) { background-color: #fafaf5; }
        .total-row td { border-top: 2px solid #3E2723; font-weight: bold; font-size: 11px; padding: 10px 6px; }
        .footer { text-align: center; margin-top: 25px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 8px; color: #A1887F; }
    </style>
</head>
<body>
    <div class="header">
        <h1>☕ Ciks Coffee</h1>
        <p>Laporan Penjualan</p>
    </div>

    <div class="meta">
        <table>
            <tr><td class="label">Periode:</td><td>{{ $dateFrom }} — {{ $dateTo }}</td></tr>
            <tr><td class="label">Total Transaksi:</td><td>{{ $orders->count() }} pesanan</td></tr>
            <tr><td class="label">Tanggal Cetak:</td><td>{{ now()->format('d/m/Y H:i') }}</td></tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>No. Order</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Kasir</th>
                <th>Bayar</th>
                <th>Item</th>
                <th class="right">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $i => $order)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $order->customer_name }}</td>
                <td>{{ $order->user?->name ?? '-' }}</td>
                <td>{{ strtoupper($order->payment_method) }}</td>
                <td>
                    @foreach($order->items as $item)
                        {{ $item->product_name }}{{ $item->variant ? " ({$item->variant})" : '' }} x{{ $item->quantity }}{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </td>
                <td class="right">{{ number_format($order->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="7" style="text-align:right">GRAND TOTAL</td>
                <td class="right">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Dicetak oleh sistem Ciks Coffee &middot; {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
