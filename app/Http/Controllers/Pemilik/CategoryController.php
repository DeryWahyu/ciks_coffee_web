<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display categories list.
     */
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return view('pemilik.products.categories', compact('categories'));
    }

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Kategori ini sudah ada.',
        ]);

        $validated['slug'] = Str::slug($validated['name']) ?: 'kategori-'.Str::uuid();
        if (Category::where('slug', $validated['slug'])->exists()) {
            throw ValidationException::withMessages(['name' => 'Identitas kategori ini sudah digunakan. Pilih nama yang berbeda.']);
        }
        Category::create($validated);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Update a category.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,'.$category->id,
        ]);

        $category->update($validated);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Toggle category status.
     */
    public function toggleStatus(Category $category)
    {
        $category->update(['is_active' => ! $category->is_active]);
        $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Kategori \"{$category->name}\" berhasil {$status}.");
    }
}
