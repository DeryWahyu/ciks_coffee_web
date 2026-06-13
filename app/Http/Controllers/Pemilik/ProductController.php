<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            $query->where('name', 'like', '%' . $request->search . '%');
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
            'price' => 'required|numeric|min:0',
            'price_lite' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
            // Non-coffee ingredients
            'ingredients' => 'nullable|array',
            'ingredients.*.id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            // Coffee variant ingredients
            'ingredients_base' => 'nullable|array',
            'ingredients_base.*.id' => 'required|exists:ingredients,id',
            'ingredients_base.*.quantity' => 'required|numeric|min:0.01',
            'ingredients_lite' => 'nullable|array',
            'ingredients_lite.*.id' => 'required|exists:ingredients,id',
            'ingredients_lite.*.quantity' => 'required|numeric|min:0.01',
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'price.required' => 'Harga wajib diisi.',
            'image.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $product = Product::create(collect($validated)->except(['ingredients', 'ingredients_base', 'ingredients_lite'])->toArray());

        // Attach ingredients with variant
        $this->syncIngredients($product, $validated);

        return redirect()->route('pemilik.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Show the form for editing a product.
     */
    public function edit(Product $product)
    {
        $product->load('ingredients');
        $categories = Category::where('is_active', true)->orderBy('name')->get();
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
            'price' => 'required|numeric|min:0',
            'price_lite' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
            'ingredients' => 'nullable|array',
            'ingredients.*.id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients_base' => 'nullable|array',
            'ingredients_base.*.id' => 'required|exists:ingredients,id',
            'ingredients_base.*.quantity' => 'required|numeric|min:0.01',
            'ingredients_lite' => 'nullable|array',
            'ingredients_lite.*.id' => 'required|exists:ingredients,id',
            'ingredients_lite.*.quantity' => 'required|numeric|min:0.01',
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'price.required' => 'Harga wajib diisi.',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $category = Category::find($validated['category_id']);
        if (!$category || !$category->isCoffee()) {
            $validated['price_lite'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $product->update(collect($validated)->except(['ingredients', 'ingredients_base', 'ingredients_lite'])->toArray());

        // Detach all existing, then re-attach with variants
        $product->ingredients()->detach();
        $this->syncIngredients($product, $validated);

        return redirect()->route('pemilik.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
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
        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Produk \"{$product->name}\" berhasil {$status}.");
    }

    /**
     * Delete a product.
     */
    public function destroy(Product $product)
    {
        // Check if the product has transaction history
        $hasTransactions = \DB::table('order_items')->where('product_id', $product->id)->exists();

        if ($hasTransactions) {
            return redirect()->route('pemilik.products.index')
                ->with('error', 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi/pesanan. Anda dapat menonaktifkan produk ini sebagai gantinya.');
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('pemilik.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
