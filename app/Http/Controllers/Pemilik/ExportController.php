<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SystemExportService;
use App\Support\ReportDateRange;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    /**
     * Prefix text that spreadsheet applications could evaluate as a formula.
     */
    private function safeSpreadsheetText(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^\s*[=+\-@]/u', $value) ? "'{$value}" : $value;
    }

    private function itemSummary(Order $order): string
    {
        $items = $order->items->map(fn ($item) => $item->product_name.($item->variant ? " ({$item->variant})" : '')." x{$item->quantity}"
        )->join(', ');

        return $this->safeSpreadsheetText($items);
    }

    private function setSpreadsheetTextCell($sheet, string $coordinate, ?string $value): void
    {
        $sheet->setCellValueExplicit(
            $coordinate,
            $this->safeSpreadsheetText($value),
            DataType::TYPE_STRING,
        );
    }

    /**
     * Display export page with filter options.
     */
    public function index()
    {
        return view('pemilik.exports.index');
    }

    private function validateFilters(Request $request): array
    {
        ReportDateRange::validate($request, 'date_from', 'date_to');

        return $request->validate([
            'scope' => ['sometimes', 'required', 'in:all,sales'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'payment_method' => ['nullable', 'in:all,cash,qris'],
        ]);
    }

    private function fullExport(string $format, array $filters)
    {
        $file = app(SystemExportService::class)->generate($format, $filters);

        return response()->download(Storage::disk(config('exports.disk'))->path($file['path']), $file['filename'], [
            'Content-Type' => $file['mime'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ])->setPrivate()->deleteFileAfterSend(true);
    }

    /**
     * Build the filtered query based on request parameters.
     */
    private function buildQuery(Request $request)
    {
        ReportDateRange::validate($request, 'date_from', 'date_to');
        $query = Order::with(['items', 'user', 'cashier'])->visibleInTransactionHistory();

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
        $filters = $this->validateFilters($request);
        if (($filters['scope'] ?? 'all') === 'all') {
            return $this->fullExport('csv', $filters);
        }
        $orders = $this->buildQuery($request)->get();

        $filename = 'laporan_penjualan_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'private, no-store',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['No', 'No. Order', 'Tanggal', 'Pelanggan', 'Kasir', 'Metode Bayar', 'Item', 'Total']);

            foreach ($orders as $i => $order) {
                fputcsv($file, [
                    $i + 1,
                    $this->safeSpreadsheetText($order->order_number),
                    $this->safeSpreadsheetText($order->created_at->format('d/m/Y H:i')),
                    $this->safeSpreadsheetText($order->customer_name),
                    $this->safeSpreadsheetText($order->cashier?->name ?? '-'),
                    $this->safeSpreadsheetText(strtoupper($order->payment_method)),
                    $this->itemSummary($order),
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
        $filters = $this->validateFilters($request);
        if (($filters['scope'] ?? 'all') === 'all') {
            return $this->fullExport('excel', $filters);
        }
        $orders = $this->buildQuery($request)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Penjualan');

        // Header styling
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3E2723']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $headers = ['No', 'No. Order', 'Tanggal', 'Pelanggan', 'Kasir', 'Metode Bayar', 'Item', 'Total (Rp)'];
        foreach ($headers as $col => $h) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1).'1', $h);
        }
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Data rows
        $row = 2;
        foreach ($orders as $i => $order) {
            $sheet->setCellValue("A{$row}", $i + 1);
            $this->setSpreadsheetTextCell($sheet, "B{$row}", $order->order_number);
            $this->setSpreadsheetTextCell($sheet, "C{$row}", $order->created_at->format('d/m/Y H:i'));
            $this->setSpreadsheetTextCell($sheet, "D{$row}", $order->customer_name);
            $this->setSpreadsheetTextCell($sheet, "E{$row}", $order->cashier?->name ?? '-');
            $this->setSpreadsheetTextCell($sheet, "F{$row}", strtoupper($order->payment_method));
            $this->setSpreadsheetTextCell($sheet, "G{$row}", $this->itemSummary($order));
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

        $filename = 'laporan_penjualan_'.now()->format('Y-m-d_His').'.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Export to PDF.
     */
    public function exportPdf(Request $request)
    {
        $filters = $this->validateFilters($request);
        if (($filters['scope'] ?? 'all') === 'all') {
            return $this->fullExport('pdf', $filters);
        }
        $orders = $this->buildQuery($request)->get();

        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->format('d/m/Y') : 'Semua';
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->format('d/m/Y') : 'Semua';
        $grandTotal = $orders->sum('total');

        $pdf = Pdf::loadView('pemilik.exports.pdf', compact('orders', 'dateFrom', 'dateTo', 'grandTotal'))
            ->setPaper('a4', 'landscape');

        $filename = 'laporan_penjualan_'.now()->format('Y-m-d_His').'.pdf';

        return $pdf->download($filename)->header('Cache-Control', 'private, no-store');
    }
}
