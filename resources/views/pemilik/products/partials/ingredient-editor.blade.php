@php
    $fields = ['default' => 'ingredients', 'base' => 'ingredients_base', 'lite' => 'ingredients_lite'];
    $existing = isset($product) ? $product->ingredients : collect();
    $ingredientRows = [];
    foreach ($fields as $variant => $field) {
        $defaults = $existing->filter(fn ($item) => ($item->pivot->variant ?? 'default') === $variant)
            ->map(fn ($item) => ['id' => $item->id, 'quantity' => $item->pivot->quantity])->values()->all();
        $values = session()->hasOldInput() ? old($field, []) : $defaults;
        $ingredientRows[$variant] = collect(is_array($values) ? $values : [])->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => ['id' => is_scalar($item['id'] ?? null) ? $item['id'] : '', 'quantity' => is_scalar($item['quantity'] ?? null) ? $item['quantity'] : ''])->values()->all();
    }
    $ingredientConfig = [
        'ingredients' => $ingredients->map(fn ($item) => ['id' => $item->id, 'nama_bahan' => $item->nama_bahan, 'satuan' => $item->satuan])->values(),
        'rows' => $ingredientRows,
    ];
@endphp
<script type="application/json" id="product-ingredient-config">@json($ingredientConfig)</script>
@vite('resources/js/product-ingredients.js')
