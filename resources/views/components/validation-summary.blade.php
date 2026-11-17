@if ($errors->any())
    <div role="alert" tabindex="-1" data-validation-summary class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-700">
        <p class="font-semibold">Data belum dapat diproses. Periksa kembali isian berikut:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach (array_unique($errors->all()) as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
