<?php

namespace App\Services;

use App\Exceptions\TableVersionConflictException;
use App\Models\CoffeeTable;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TableAvailabilityService
{
    /**
     * Sources that are allowed to create a manual status audit record.
     */
    private const ALLOWED_SOURCES = [
        'employee_web',
        'owner_web',
    ];

    /**
     * Change a table's availability and write an immutable audit record.
     *
     * The table row is locked and its version is checked inside the same
     * transaction. This prevents concurrent employee updates from silently
     * replacing a more recent status.
     *
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws TableVersionConflictException
     */
    public function changeStatus(
        CoffeeTable $coffeeTable,
        User $actor,
        string $newStatus,
        ?string $note,
        int $expectedVersion,
        string $source,
    ): CoffeeTable {
        $this->assertActorCanUpdateStatus($actor);

        if (!CoffeeTable::isValidStatus($newStatus)) {
            throw new InvalidArgumentException('Status meja tidak valid.');
        }

        if (!in_array($source, self::ALLOWED_SOURCES, true)) {
            throw new InvalidArgumentException('Sumber pembaruan status tidak valid.');
        }

        $normalizedNote = $this->normalizeNote($note);

        return DB::transaction(function () use (
            $coffeeTable,
            $actor,
            $newStatus,
            $normalizedNote,
            $expectedVersion,
            $source,
        ): CoffeeTable {
            $lockedTable = CoffeeTable::query()
                ->whereKey($coffeeTable->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedTable->is_active || ! $lockedTable->floorLayout()
                ->where('is_active', true)
                ->exists()) {
                throw new InvalidArgumentException('Meja tidak tersedia untuk diperbarui.');
            }

            if ($lockedTable->version !== $expectedVersion) {
                throw new TableVersionConflictException($lockedTable);
            }

            if ($lockedTable->status === $newStatus && $lockedTable->status_note === $normalizedNote) {
                return $lockedTable->load(['floorLayout', 'statusUpdatedBy']);
            }

            $oldStatus = $lockedTable->status;

            $lockedTable->forceFill([
                'status' => $newStatus,
                'status_note' => $normalizedNote,
                'status_updated_by' => $actor->id,
                'status_updated_at' => now(),
                'version' => $lockedTable->version + 1,
            ])->save();

            $lockedTable->statusHistories()->create([
                'user_id' => $actor->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $normalizedNote,
                'source' => $source,
            ]);

            return $lockedTable->fresh(['floorLayout', 'statusUpdatedBy']);
        }, 3);
    }

    /**
     * @throws AuthorizationException
     */
    private function assertActorCanUpdateStatus(User $actor): void
    {
        if (! $actor->is_active || (! $actor->isPemilik() && ! $actor->isKaryawan())) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk memperbarui status meja.');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function normalizeNote(?string $note): ?string
    {
        $normalizedNote = $note === null ? null : trim($note);

        if ($normalizedNote === null || $normalizedNote === '') {
            return null;
        }

        if (mb_strlen($normalizedNote) > 500) {
            throw new InvalidArgumentException('Catatan status meja maksimal 500 karakter.');
        }

        return $normalizedNote;
    }
}
