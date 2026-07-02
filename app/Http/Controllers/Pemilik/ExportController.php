<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    /**
     * Display export page with filter options.
     */
    public function index()
    {
        return view('pemilik.exports.index');
    }

    /**
     * Build the filtered query based on request parameters.
     */
    private function buildQuery(Request $request)
    {
        $query = Order::with(['items', 'user', 'cashier'])
            ->whereIn('status', ['selesai', 'diambil']);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * Export to CSV.
     */
    public function exportCsv(Request $request)
    {
        $orders = $this->buildQuery($request)->get();

        $filename = 'laporan_penjualan_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['No', 'No. Order', 'Tanggal', 'Pelanggan', 'Kasir', 'Metode Bayar', 'Item', 'Total']);

            foreach ($orders as $i => $order) {
                $items = $order->items->map(fn($item) =>
                    $item->product_name . ($item->variant ? " ({$item->variant})" : '') . " x{$item->quantity}"
                )->join(', ');

                fputcsv($file, [
                    $i + 1,
                    $order->order_number,
                    $order->created_at->format('d/m/Y H:i'),
                    $order->customer_name,
                    $order->cashier?->name ?? '-',
                    strtoupper($order->payment_method),
                    $items,
                    $order->total,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to Excel XLSX using PhpSpreadsheet directly.
     */
    public function exportExcel(Request $request)
    {
        $orders = $this->buildQuery($request)->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Penjualan');

        // Header styling
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '3E2723']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $headers = ['No', 'No. Order', 'Tanggal', 'Pelanggan', 'Kasir', 'Metode Bayar', 'Item', 'Total (Rp)'];
        foreach ($headers as $col => $h) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1', $h);
        }
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Data rows
        $row = 2;
        foreach ($orders as $i => $order) {
            $items = $order->items->map(fn($item) =>
                $item->product_name . ($item->variant ? " ({$item->variant})" : '') . " x{$item->quantity}"
            )->join(', ');

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $order->order_number);
            $sheet->setCellValue("C{$row}", $order->created_at->format('d/m/Y H:i'));
            $sheet->setCellValue("D{$row}", $order->customer_name);
            $sheet->setCellValue("E{$row}", $order->cashier?->name ?? '-');
            $sheet->setCellValue("F{$row}", strtoupper($order->payment_method));
            $sheet->setCellValue("G{$row}", $items);
            $sheet->setCellValue("H{$row}", (float) $order->total);
            $row++;
        }

        // Total row
        $sheet->setCellValue("G{$row}", 'GRAND TOTAL');
        $sheet->setCellValue("H{$row}", $orders->sum('total'));
        $sheet->getStyle("G{$row}:H{$row}")->getFont()->setBold(true);

        // Auto-width
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Number format for Total column
        $sheet->getStyle("H2:H{$row}")->getNumberFormat()
            ->setFormatCode('#,##0');

        $filename = 'laporan_penjualan_' . now()->format('Y-m-d_His') . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export to PDF.
     */
    public function exportPdf(Request $request)
    {
        $orders = $this->buildQuery($request)->get();

        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->format('d/m/Y') : 'Semua';
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->format('d/m/Y') : 'Semua';
        $grandTotal = $orders->sum('total');

        $pdf = Pdf::loadView('pemilik.exports.pdf', compact('orders', 'dateFrom', 'dateTo', 'grandTotal'))
            ->setPaper('a4', 'landscape');

        $filename = 'laporan_penjualan_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }
}
