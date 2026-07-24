<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FloorLayout;
use App\Support\TableLayoutPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TableLayoutController extends Controller
{
    /**
     * Return the active layout for the authenticated mobile customer.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::forUser($request->user())->authorize('viewAny', FloorLayout::class);

        $floorLayout = FloorLayout::query()
            ->where('is_active', true)
            ->with([
                'coffeeTables' => fn ($query) => $query->active()->orderBy('code'),
            ])
            ->first();

        if ($floorLayout === null) {
            return response()->json([
                'success' => false,
                'message' => 'Denah meja belum tersedia.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => TableLayoutPresenter::forCustomer($floorLayout),
        ]);
    }
}
