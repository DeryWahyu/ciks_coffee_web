<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Lengkap Ciks Coffee</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #3e2723; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 13px; padding: 9px; background: #f5efe8; }
        .section { page-break-before: always; }
        .section:first-child { page-break-before: auto; }
        .muted { color: #795548; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 8px 0 16px; }
        td, th { border: 1px solid #dfd0c3; padding: 5px; text-align: left; vertical-align: top; word-wrap: break-word; }
        th { background: #f5efe8; font-weight: bold; width: 19%; }
        tr { page-break-inside: avoid; }
        .record { font-size: 8px; color: #795548; margin: 12px 0 2px; }
    </style>
</head>
<body>
@foreach($sections as $section)
    <div class="section">
        @if($loop->first)
            <x-brand-logo :inline="true" :wordmark="true" style="display:block;width:88px;height:40px;" />
            <h1>Ciks Coffee &middot; Data Lengkap</h1>
            <p class="muted">Dibuat {{ now()->format('d/m/Y H:i') }} ({{ config('app.timezone') }}). Lampiran gambar tersedia dalam arsip CSV ZIP.</p>
        @endif
        <h2>{{ $section['title'] }} <span class="muted">({{ count($section['rows']) }} baris)</span></h2>
        @forelse($section['rows'] as $row)
            @if(count($section['headers']) > 2)<p class="record">Data {{ $loop->iteration }}</p>@endif
            <table>
                @foreach($section['headers'] as $index => $header)
                    <tr><th>{{ $header }}</th><td>{{ $row[$index] ?? '—' }}</td></tr>
                @endforeach
            </table>
        @empty
            <p class="muted">Tidak ada data pada cakupan ini.</p>
        @endforelse
    </div>
@endforeach
</body>
</html>
