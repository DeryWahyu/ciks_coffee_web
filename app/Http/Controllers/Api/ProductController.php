<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get all active categories.
     */
    public function categories()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get products, optionally filtered by category.
     */
    public function index(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);

        if ($request->has('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->get();

        // Map products to include the relative image URL
        $products->map(function ($product) {
            $product->image_url = $product->image ? 'storage/' . $product->image : null;
            return $product;
        });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}
