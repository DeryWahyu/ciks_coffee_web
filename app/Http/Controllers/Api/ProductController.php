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
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ]);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get products, optionally filtered by category.
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with('category:id,name,slug')
            ->where('is_active', true);

        if ($request->has('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $products = $query->latest()->get();

        // Return only fields required by the mobile catalogue. Recipe, stock,
        // pivot, and internal model metadata must never be serialized here.
        $products = $products->map(function (Product $product): array {
            $reason = null;
            $isAvailable = $product->isAvailable($reason);

            return [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'description' => $product->description,
                'image_url' => $product->image ? 'storage/'.$product->image : null,
                'price' => $product->price,
                'price_lite' => $product->price_lite,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
                'is_available' => $isAvailable,
                'unavailable_reason' => $isAvailable
                    ? null
                    : 'Produk sedang tidak tersedia.',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
