<?php

namespace App\Http\Controllers\Pemilik;

use App\Exceptions\TableVersionConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoffeeTableRequest;
use App\Http\Requests\DeleteCoffeeTableRequest;
use App\Http\Requests\UpdateCoffeeTableRequest;
use App\Http\Requests\UpdateFloorLayoutRequest;
use App\Http\Requests\UpdateTableActiveRequest;
use App\Http\Requests\UpdateTableStatusRequest;
use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Models\TableStatusHistory;
use App\Services\TableAvailabilityService;
use App\Services\TableLayoutService;
use App\Support\TableLayoutPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class TableController extends Controller
{
    /**
     * Display the owner monitoring and layout-editor workspace.
     */
    public function index()
    {
        return view('pemilik.tables.index');
    }

    /**
     * JSON source for the owner monitoring and layout editor screen in Stage 4.
     */
    public function layout(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'layout_id' => ['nullable', 'integer', 'exists:floor_layouts,id'],
        ]);

        $query = FloorLayout::query()->with([
            'coffeeTables' => fn ($tableQuery) => $tableQuery
                ->with('statusUpdatedBy:id,name')
                ->orderBy('code'),
        ]);

        $floorLayout = isset($filters['layout_id'])
            ? $query->find($filters['layout_id'])
            : $query->where('is_active', true)->first();

        if ($floorLayout === null) {
            return response()->json([
                'success' => false,
                'message' => 'Denah meja belum tersedia.',
            ], 404);
        }

        Gate::forUser($request->user())->authorize('manage', $floorLayout);

        return response()->json([
            'success' => true,
            'data' => TableLayoutPresenter::forStaff($floorLayout, true),
        ]);
    }

    /**
     * Save a deliberate batch of position changes made in the owner layout editor.
     */
    public function updateLayout(
        UpdateFloorLayoutRequest $request,
        FloorLayout $floorLayout,
        TableLayoutService $tableLayoutService,
    ): JsonResponse {
        Gate::forUser($request->user())->authorize('manage', $floorLayout);

        $data = $request->validated();

        try {
            $updatedLayout = $tableLayoutService->updateLayout(
                $floorLayout,
                $request->user(),
                Arr::except($data, 'tables'),
                $data['tables'],
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
            'message' => 'Denah meja berhasil disimpan.',
            'data' => TableLayoutPresenter::forStaff($updatedLayout, true),
        ]);
    }

    /**
     * Add a table to an existing layout.
     */
    public function store(StoreCoffeeTableRequest $request, TableLayoutService $tableLayoutService): JsonResponse
    {
        Gate::forUser($request->user())->authorize('create', CoffeeTable::class);

        $data = $request->validated();
        $floorLayout = FloorLayout::query()->findOrFail($data['floor_layout_id']);
        Gate::forUser($request->user())->authorize('manage', $floorLayout);

        try {
            $coffeeTable = $tableLayoutService->createTable($floorLayout, $request->user(), $data);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil ditambahkan.',
            'data' => TableLayoutPresenter::table($coffeeTable),
        ], 201);
    }

    /**
     * Update table metadata and geometry; status is intentionally handled separately.
     */
    public function update(
        UpdateCoffeeTableRequest $request,
        CoffeeTable $coffeeTable,
        TableLayoutService $tableLayoutService,
    ): JsonResponse {
        Gate::forUser($request->user())->authorize('update', $coffeeTable);

        $data = $request->validated();

        try {
            $updatedTable = $tableLayoutService->updateTable(
                $coffeeTable,
                $request->user(),
                Arr::except($data, 'version'),
                $data['version'],
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
            'message' => 'Konfigurasi meja berhasil diperbarui.',
            'data' => TableLayoutPresenter::table($updatedTable),
        ]);
    }

    /**
     * Permanently remove a table that has not entered the status audit trail.
     */
    public function destroy(
        DeleteCoffeeTableRequest $request,
        CoffeeTable $coffeeTable,
        TableLayoutService $tableLayoutService,
    ): JsonResponse {
        Gate::forUser($request->user())->authorize('delete', $coffeeTable);

        try {
            $tableLayoutService->deleteTable(
                $coffeeTable,
                $request->user(),
                $request->validated('version'),
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
            'message' => 'Meja berhasil dihapus.',
        ]);
    }

    /**
     * Archive or restore a table without losing its audit history.
     */
    public function updateActiveState(
        UpdateTableActiveRequest $request,
        CoffeeTable $coffeeTable,
        TableLayoutService $tableLayoutService,
    ): JsonResponse {
        Gate::forUser($request->user())->authorize('toggleActive', $coffeeTable);

        $data = $request->validated();

        try {
            $updatedTable = $tableLayoutService->updateActiveState(
                $coffeeTable,
                $request->user(),
                (bool) $data['is_active'],
                $data['version'],
            );
        } catch (TableVersionConflictException $exception) {
            return $this->conflictResponse($exception);
        }

        return response()->json([
            'success' => true,
            'message' => $updatedTable->is_active
                ? 'Meja berhasil diaktifkan.'
                : 'Meja berhasil diarsipkan.',
            'data' => TableLayoutPresenter::table($updatedTable),
        ]);
    }

    /**
     * Owner status changes use the same audited, optimistic-locking service as employees.
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
                'owner_web',
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

    /**
     * Paginated audit feed for the owner monitoring screen.
     */
    public function history(Request $request): JsonResponse
    {
        Gate::forUser($request->user())->authorize('viewHistory', CoffeeTable::class);

        $filters = $request->validate([
            'coffee_table_id' => ['nullable', 'integer', 'exists:coffee_tables,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', Rule::in(CoffeeTable::statuses())],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $histories = TableStatusHistory::query()
            ->with(['coffeeTable:id,code,name', 'user:id,name'])
            ->when(isset($filters['coffee_table_id']), fn ($query) => $query->where('coffee_table_id', $filters['coffee_table_id']))
            ->when(isset($filters['user_id']), fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when(isset($filters['status']), fn ($query) => $query->where('new_status', $filters['status']))
            ->when(isset($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('created_at')
            ->paginate($filters['per_page'] ?? 20)
            ->through(function (TableStatusHistory $history): array {
                return [
                    'id' => $history->id,
                    'coffee_table' => $history->coffeeTable === null ? null : [
                        'id' => $history->coffeeTable->id,
                        'code' => $history->coffeeTable->code,
                        'name' => $history->coffeeTable->name,
                    ],
                    'changed_by' => $history->user === null ? null : [
                        'id' => $history->user->id,
                        'name' => $history->user->name,
                    ],
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'note' => $history->note,
                    'source' => $history->source,
                    'changed_at' => $history->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $histories->items(),
            'meta' => [
                'current_page' => $histories->currentPage(),
                'last_page' => $histories->lastPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
            ],
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
