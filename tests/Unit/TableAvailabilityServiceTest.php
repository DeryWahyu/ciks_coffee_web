<?php

namespace Tests\Unit;

use App\Exceptions\TableVersionConflictException;
use App\Models\CoffeeTable;
use App\Models\TableStatusHistory;
use App\Models\User;
use App\Services\TableAvailabilityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTableAvailabilityFixtures;
use Tests\TestCase;

class TableAvailabilityServiceTest extends TestCase
{
    use CreatesTableAvailabilityFixtures;
    use RefreshDatabase;

    public function test_employee_status_change_is_versioned_and_audited(): void
    {
        $layout = $this->createFloorLayout();
        $table = $this->createCoffeeTable($layout);
        $employee = $this->createTableUser(User::ROLE_KARYAWAN);

        $updated = app(TableAvailabilityService::class)->changeStatus(
            $table,
            $employee,
            CoffeeTable::STATUS_OCCUPIED,
            '  Pelanggan sudah datang.  ',
            1,
            'employee_web',
        );

        $this->assertSame(CoffeeTable::STATUS_OCCUPIED, $updated->status);
        $this->assertSame(2, $updated->version);
        $this->assertSame('Pelanggan sudah datang.', $updated->status_note);
        $this->assertSame($employee->id, $updated->status_updated_by);
        $this->assertDatabaseHas('table_status_histories', [
            'coffee_table_id' => $table->id,
            'user_id' => $employee->id,
            'old_status' => CoffeeTable::STATUS_AVAILABLE,
            'new_status' => CoffeeTable::STATUS_OCCUPIED,
            'note' => 'Pelanggan sudah datang.',
            'source' => 'employee_web',
        ]);
    }

    public function test_stale_version_is_rejected_without_an_extra_audit_record(): void
    {
        $layout = $this->createFloorLayout();
        $table = $this->createCoffeeTable($layout);
        $employee = $this->createTableUser(User::ROLE_KARYAWAN);
        $service = app(TableAvailabilityService::class);

        $service->changeStatus(
            $table,
            $employee,
            CoffeeTable::STATUS_OCCUPIED,
            null,
            1,
            'employee_web',
        );

        try {
            $service->changeStatus(
                $table,
                $employee,
                CoffeeTable::STATUS_RESERVED,
                null,
                1,
                'employee_web',
            );
            $this->fail('Versi stale seharusnya menghasilkan konflik.');
        } catch (TableVersionConflictException) {
            // Expected: the latest status must not be overwritten silently.
        }

        $this->assertDatabaseCount('table_status_histories', 1);
        $this->assertSame(CoffeeTable::STATUS_OCCUPIED, $table->fresh()->status);
    }

    public function test_regular_customer_cannot_change_a_table_status(): void
    {
        $layout = $this->createFloorLayout();
        $table = $this->createCoffeeTable($layout);
        $customer = $this->createTableUser();

        $this->expectException(AuthorizationException::class);

        app(TableAvailabilityService::class)->changeStatus(
            $table,
            $customer,
            CoffeeTable::STATUS_OCCUPIED,
            null,
            1,
            'employee_web',
        );
    }

    public function test_unchanged_status_does_not_create_an_audit_record(): void
    {
        $layout = $this->createFloorLayout();
        $table = $this->createCoffeeTable($layout);
        $owner = $this->createTableUser(User::ROLE_PEMILIK);

        $updated = app(TableAvailabilityService::class)->changeStatus(
            $table,
            $owner,
            CoffeeTable::STATUS_AVAILABLE,
            null,
            1,
            'owner_web',
        );

        $this->assertSame(1, $updated->version);
        $this->assertDatabaseCount('table_status_histories', 0);
    }
}
