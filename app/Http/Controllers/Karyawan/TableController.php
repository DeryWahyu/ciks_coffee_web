<?php

namespace App\Http\Controllers\Karyawan;

use App\Exceptions\TableVersionConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTableStatusRequest;
use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Services\TableAvailabilityService;
use App\Support\TableLayoutPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class TableController extends Controller
{
    public function index()
    {
        return view('karyawan.tables.index');
    }

    /**
     * JSON data source for the employee availability screen in Stage 3.
     */
    public function layout(Request $request): JsonResponse
    {
        Gate::forUser($request->user())->authorize('viewAny', FloorLayout::class);

        $floorLayout = FloorLayout::query()
            ->where('is_active', true)
            ->with([
                'coffeeTables' => fn ($query) => $query->active()
                    ->with('statusUpdatedBy:id,name')
                    ->orderBy('code'),
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
            'data' => TableLayoutPresenter::forStaff($floorLayout),
        ]);
    }

    /**
     * Update one operational table status without exposing layout editing rights.
     */
    public function updateStatus(
        UpdateTableStatusRequest $request,
        CoffeeTable $coffeeTable,
        TableAvailabilityService $tableAvailabilityService,
    ): JsonResponse {
        Gate::forUser($request->user())->authorize('updateStatus', $coffeeTable);

        $coffeeTable->load('floorLayout');
        if (! $coffeeTable->is_active || ! $coffeeTable->floorLayout?->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Meja tidak tersedia untuk diperbarui.',
            ], 404);
        }

        $data = $request->validated();

        try {
            $updatedTable = $tableAvailabilityService->changeStatus(
                $coffeeTable,
                $request->user(),
                $data['status'],
                $data['note'] ?? null,
                $data['version'],
                'employee_web',
            );
        } catch (TableVersionConflictException $exception) {
            return $this->conflictResponse($exception);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status meja berhasil diperbarui.',
            'data' => TableLayoutPresenter::table($updatedTable),
        ]);
    }

    private function conflictResponse(TableVersionConflictException $exception): JsonResponse
    {
        $currentTable = $exception->coffeeTable->load('statusUpdatedBy:id,name');

        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'data' => TableLayoutPresenter::table($currentTable),
        ], 409);
    }
}
