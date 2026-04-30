<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /**
     * Display a listing of ingredients.
     */
    public function index(Request $request)
    {
        $query = Ingredient::query();

        if ($request->filled('search')) {
            $query->where('nama_bahan', 'like', '%' . $request->search . '%');
        }

        $ingredients = $query->orderBy('nama_bahan')->paginate(15)->withQueryString();

        return view('pemilik.materials.index', compact('ingredients'));
    }

    /**
     * Store a new ingredient.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_bahan' => 'required|string|max:255|unique:ingredients,nama_bahan',
            'satuan' => 'required|string|max:50',
            'stok' => 'required|numeric|min:0',
        ], [
            'nama_bahan.required' => 'Nama bahan wajib diisi.',
            'nama_bahan.unique' => 'Bahan baku ini sudah ada.',
            'satuan.required' => 'Satuan wajib diisi.',
            'stok.required' => 'Stok awal wajib diisi.',
        ]);

        Ingredient::create($validated);

        return back()->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    /**
     * Update an ingredient.
     */
    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'nama_bahan' => 'required|string|max:255|unique:ingredients,nama_bahan,' . $ingredient->id,
            'satuan' => 'required|string|max:50',
            'stok' => 'required|numeric|min:0',
        ], [
            'nama_bahan.required' => 'Nama bahan wajib diisi.',
            'nama_bahan.unique' => 'Bahan baku ini sudah ada.',
        ]);

        $ingredient->update($validated);

        return back()->with('success', 'Bahan baku berhasil diperbarui.');
    }

    /**
     * Delete an ingredient.
     */
    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();

        return back()->with('success', "Bahan baku \"{$ingredient->nama_bahan}\" berhasil dihapus.");
    }
}
