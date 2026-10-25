<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $products = $query->latest()->paginate(12)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('pemilik.products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $ingredients = Ingredient::orderBy('nama_bahan')->get();

        return view('pemilik.products.create', compact('categories', 'ingredients'));
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'price_lite' => 'nullable|numeric|decimal:0,2|min:0|max:9999999999.99',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
            // Non-coffee ingredients
            'ingredients' => 'nullable|array',
            'ingredients.*.id' => 'required|integer|distinct|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            // Coffee variant ingredients
            'ingredients_base' => 'nullable|array',
            'ingredients_base.*.id' => 'required|integer|distinct|exists:ingredients,id',
            'ingredients_base.*.quantity' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'ingredients_lite' => 'nullable|array',
            'ingredients_lite.*.id' => 'required|integer|distinct|exists:ingredients,id',
            'ingredients_lite.*.quantity' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'price.required' => 'Harga wajib diisi.',
            'image.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        $validated = $this->validateRecipeConfiguration($validated, $request);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
            if (! $validated['image']) {
                throw ValidationException::withMessages(['image' => 'Gambar produk gagal disimpan.']);
            }
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            DB::transaction(function () use ($validated) {
                $product = Product::create(collect($validated)->except(['ingredients', 'ingredients_base', 'ingredients_lite'])->toArray());
                $this->syncIngredients($product, $validated);
            }, 3);
        } catch (\Throwable $exception) {
            if (isset($validated['image']) && is_string($validated['image'])) {
                $this->deleteImage($validated['image']);
            }
            throw $exception;
        }

        return redirect()->route('pemilik.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Show the form for editing a product.
     */
    public function edit(Product $product)
    {
        $product->load('ingredients');
        $categories = Category::where('is_active', true)->orWhere('id', $product->category_id)->orderBy('name')->get();
        $ingredients = Ingredient::orderBy('nama_bahan')->get();

        return view('pemilik.products.edit', compact('product', 'categories', 'ingredients'));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'price_lite' => 'nullable|numeric|decimal:0,2|min:0|max:9999999999.99',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
            'ingredients' => 'nullable|array',
            'ingredients.*.id' => 'required|integer|distinct|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'ingredients_base' => 'nullable|array',
            'ingredients_base.*.id' => 'required|integer|distinct|exists:ingredients,id',
            'ingredients_base.*.quantity' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'ingredients_lite' => 'nullable|array',
            'ingredients_lite.*.id' => 'required|integer|distinct|exists:ingredients,id',
            'ingredients_lite.*.quantity' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'price.required' => 'Harga wajib diisi.',
        ]);

        $validated = $this->validateRecipeConfiguration($validated, $request);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
            if (! $validated['image']) {
                throw ValidationException::withMessages(['image' => 'Gambar produk gagal disimpan.']);
            }
        }

        $category = Category::find($validated['category_id']);
        if (! $category || ! $category->isCoffee()) {
            $validated['price_lite'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $previousImage = null;
        try {
            DB::transaction(function () use ($product, $validated, &$previousImage) {
                $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
                $previousImage = $lockedProduct->image;
                $lockedProduct->update(collect($validated)->except(['ingredients', 'ingredients_base', 'ingredients_lite'])->toArray());
                $lockedProduct->ingredients()->detach();
                $this->syncIngredients($lockedProduct, $validated);
            }, 3);
        } catch (\Throwable $exception) {
            if (isset($validated['image']) && is_string($validated['image'])) {
                $this->deleteImage($validated['image']);
            }
            throw $exception;
        }
        if (isset($validated['image']) && $previousImage && $previousImage !== $validated['image']) {
            $this->deleteImage($previousImage);
        }

        return redirect()->route('pemilik.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    private function validateRecipeConfiguration(array $validated, Request $request): array
    {
        $category = Category::findOrFail($validated['category_id']);
        if (! $category->isCoffee()) {
            $validated['price_lite'] = null;
        }
        if ($request->boolean('is_active', true)) {
            $fields = $category->isCoffee() ? ['ingredients_base'] : ['ingredients'];
            if ($category->isCoffee() && isset($validated['price_lite'])) {
                $fields[] = 'ingredients_lite';
            }
            foreach ($fields as $field) {
                if (empty($validated[$field])) {
                    throw ValidationException::withMessages([$field => 'Produk aktif wajib memiliki resep untuk setiap varian yang dijual.']);
                }
            }
        }

        return $validated;
    }

    private function deleteImage(string $path): void
    {
        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Sync ingredients to product with variant support.
     */
    private function syncIngredients(Product $product, array $validated): void
    {
        $category = Category::find($validated['category_id']);
        $isCoffee = $category && $category->isCoffee();

        if ($isCoffee) {
            // Coffee: attach base and lite variants separately
            $this->attachVariantIngredients($product, $validated['ingredients_base'] ?? [], 'base');
            $this->attachVariantIngredients($product, $validated['ingredients_lite'] ?? [], 'lite');
        } else {
            // Non-coffee: attach with null variant
            $this->attachVariantIngredients($product, $validated['ingredients'] ?? [], null);
        }
    }

    /**
     * Attach ingredients for a specific variant.
     */
    private function attachVariantIngredients(Product $product, array $items, ?string $variant): void
    {
        foreach ($items as $item) {
            $product->ingredients()->attach($item['id'], [
                'quantity' => $item['quantity'],
                'variant' => $variant,
            ]);
        }
    }

    /**
     * Toggle product active status.
     */
    public function toggleStatus(Product $product)
    {
        try {
            $product = DB::transaction(function () use ($product) {
                $locked = Product::query()->with('category')->lockForUpdate()->findOrFail($product->id);
                if (! $locked->is_active) {
                    $variants = $locked->category->isCoffee() ? ['base'] : [null];
                    if ($locked->category->isCoffee() && $locked->price_lite !== null) {
                        $variants[] = 'lite';
                    }
                    foreach ($variants as $variant) {
                        if ($locked->ingredientsByVariant($variant)->isEmpty()) {
                            throw new \DomainException('Lengkapi resep setiap varian sebelum mengaktifkan produk.');
                        }
                    }
                }
                $locked->update(['is_active' => ! $locked->is_active]);

                return $locked;
            }, 3);
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        $status = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Produk \"{$product->name}\" berhasil {$status}.");
    }

    /**
     * Delete a product.
     */
    public function destroy(Product $product)
    {
        $image = null;
        $deleted = DB::transaction(function () use ($product, &$image) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            if (DB::table('order_items')->where('product_id', $locked->id)->exists()) {
                return false;
            }
            $image = $locked->image;
            $locked->delete();

            return true;
        }, 3);
        if (! $deleted) {
            return redirect()->route('pemilik.products.index')
                ->with('error', 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi/pesanan. Anda dapat menonaktifkan produk ini sebagai gantinya.');
        }
        if ($image) {
            $this->deleteImage($image);
        }

        return redirect()->route('pemilik.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
