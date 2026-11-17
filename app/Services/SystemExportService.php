<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;
use ZipArchive;

class SystemExportService
{
    public function __construct(private SystemExportData $data) {}

    public function generate(string $format, array $filters = []): array
    {
        $extension = match ($format) {
            'csv' => 'zip', 'excel' => 'xlsx', 'pdf' => 'pdf',
            default => throw new \InvalidArgumentException('Format ekspor tidak dikenal.'),
        };
        $filename = 'cikscoffee_data_lengkap_'.now()->format('Y-m-d_His').'.'.$extension;
        $path = 'exports/'.Str::uuid().'-'.$filename;
        $disk = Storage::disk(config('exports.disk'));
        if (! $disk->makeDirectory('exports')) {
            throw new RuntimeException('Direktori ekspor tidak dapat dibuat.');
        }
        $target = $disk->path($path);

        try {
            // All datasets share the same read transaction, without locking business rows.
            DB::transaction(function () use ($format, $filters, $target) {
                $sections = $this->data->sections($filters);
                match ($format) {
                    'csv' => $this->writeCsvArchive($target, $sections, $filters),
                    'excel' => $this->writeExcel($target, $sections),
                    'pdf' => $this->writePdf($target, $sections),
                };
            });
        } catch (Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        return ['path' => $path, 'filename' => $filename, 'mime' => self::mime($format)];
    }

    public static function mime(string $format): string
    {
        return match ($format) {
            'csv' => 'application/zip',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/pdf',
        };
    }

    private function safeText(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return preg_match('/^\s*[=+\-@]/u', $text) ? "'{$text}" : $text;
    }

    private function writeCsvArchive(string $target, array $sections, array $filters): void
    {
        $zip = new ZipArchive;
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Arsip ekspor tidak dapat dibuat.');
        }
        $temporary = [];
        $counts = [];
        try {
            foreach ($sections as $key => $section) {
                $temp = tempnam(dirname($target), 'csv-');
                if ($temp === false) {
                    throw new RuntimeException('Berkas CSV sementara tidak dapat dibuat.');
                }
                $temporary[] = $temp;
                $file = fopen($temp, 'wb');
                if ($file === false) {
                    throw new RuntimeException('Berkas CSV tidak dapat ditulis.');
                }
                $counts[$key] = 0;
                try {
                    fwrite($file, "\xEF\xBB\xBF");
                    fputcsv($file, $section['headers'], ',', '"', '');
                    foreach (($section['rows'])() as $row) {
                        if (fputcsv($file, array_map($this->safeText(...), $row), ',', '"', '') === false) {
                            throw new RuntimeException('Penulisan CSV gagal.');
                        }
                        $counts[$key]++;
                    }
                } finally {
                    fclose($file);
                }
                if (! $zip->addFile($temp, $key.'.csv')) {
                    throw new RuntimeException('CSV tidak dapat ditambahkan ke arsip.');
                }
            }
            $media = $this->data->media($filters);
            foreach ($media as $entry) {
                if ($entry['source'] && ! $zip->addFile($entry['source'], $entry['archive'])) {
                    throw new RuntimeException('Lampiran tidak dapat ditambahkan ke arsip.');
                }
            }
            $zip->addFromString('manifest.json', json_encode([
                'schema_version' => 1, 'system' => 'Ciks Coffee', 'exported_at' => now()->toIso8601String(),
                'timezone' => config('app.timezone'), 'filters' => $filters, 'row_counts' => $counts,
                'media' => array_map(fn ($m) => array_diff_key($m, ['source' => true]), $media),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $zip->addFromString('BACA_SAYA.txt', "Arsip data bisnis Ciks Coffee\n\nBuka ringkasan.csv terlebih dahulu. ID menghubungkan pesanan, item, produk, resep, bahan, akun dan meja.\nTransaksi mengikuti aturan riwayat; pesanan mobile yang belum diambil hanya ada di pesanan.csv.\nFilter tanggal/metode berlaku untuk pesanan dan turunannya; tanggal juga berlaku untuk riwayat meja. Master data adalah kondisi saat ekspor.\nBerkas yang hilang ditandai dalam berkas.csv dan manifest.json.\nPassword, token, sesi dan .env tidak disertakan. Arsip ini tidak menggantikan backup pemulihan server.\n");
            if (! $zip->close()) {
                throw new RuntimeException('Arsip ekspor gagal diselesaikan.');
            }
        } finally {
            // ZipArchive may retain file handles until it is released after an exception.
            unset($zip);
            foreach ($temporary as $temp) {
                if (is_file($temp)) {
                    unlink($temp);
                }
            }
        }
    }

    private function writeExcel(string $target, array $sections): void
    {
        $book = new Spreadsheet;
        $count = 0;
        try {
            foreach (array_values($sections) as $index => $section) {
                $sheet = $index === 0 ? $book->getActiveSheet() : $book->createSheet();
                $sheet->setTitle($section['title']);
                $sheet->fromArray($section['headers'], null, 'A1');
                $lastColumn = Coordinate::stringFromColumnIndex(count($section['headers']));
                $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3E2723']],
                ]);
                $sheet->freezePane('A2');
                $rowNumber = 2;
                foreach (($section['rows'])() as $row) {
                    if (++$count > config('exports.excel_max_rows')) {
                        $this->documentTooLarge('Excel');
                    }
                    foreach ($row as $column => $value) {
                        $coordinate = Coordinate::stringFromColumnIndex($column + 1).$rowNumber;
                        if (is_int($value) || is_float($value)) {
                            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_NUMERIC);
                        } else {
                            $text = $this->safeText($value);
                            if (mb_strlen($text) > 32767) {
                                $this->documentTooLarge('Excel');
                            }
                            if ($this->isAmountColumn($section['headers'][$column]) && $value !== null && is_numeric($value)) {
                                $sheet->setCellValueExplicit($coordinate, (float) $value, DataType::TYPE_NUMERIC);
                            } else {
                                $sheet->setCellValueExplicit($coordinate, $text, DataType::TYPE_STRING);
                            }
                        }
                    }
                    $rowNumber++;
                }
                $sheet->setAutoFilter('A1:'.$lastColumn.max(1, $rowNumber - 1));
                foreach ($section['headers'] as $column => $header) {
                    $letter = Coordinate::stringFromColumnIndex($column + 1);
                    $sheet->getColumnDimension($letter)->setWidth($column === 0 ? 18 : 28);
                    if ($this->isAmountColumn($header) && $rowNumber > 2) {
                        $sheet->getStyle($letter.'2:'.$letter.($rowNumber - 1))->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                }
            }
            $book->setActiveSheetIndex(0);
            (new Xlsx($book))->save($target);
        } finally {
            $book->disconnectWorksheets();
        }
    }

    private function isAmountColumn(string $header): bool
    {
        return in_array($header, ['Total', 'Uang Diterima', 'Kembalian', 'Harga Satuan', 'Subtotal', 'Harga Base/Utama', 'Harga Lite', 'Stok', 'Kebutuhan Per Porsi', 'Jumlah Terpakai'], true);
    }

    private function writePdf(string $target, array $sections): void
    {
        $count = 0;
        foreach ($sections as &$section) {
            $rows = [];
            foreach (($section['rows'])() as $row) {
                if (++$count > config('exports.pdf_max_rows')) {
                    $this->documentTooLarge('PDF');
                }
                $rows[] = $row;
            }
            $section['rows'] = $rows;
        }
        unset($section);
        Pdf::loadView('pemilik.exports.system-pdf', compact('sections'))->setPaper('a4', 'landscape')->save($target);
    }

    private function documentTooLarge(string $format): never
    {
        throw ValidationException::withMessages(['format' => "Data terlalu besar untuk {$format}. Gunakan CSV (ZIP) untuk mengunduh seluruh data tanpa pemotongan."]);
    }
}
