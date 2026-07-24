<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'coffee_table_id',
        'user_id',
        'old_status',
        'new_status',
        'note',
        'source',
    ];

    /**
     * The table whose availability changed.
     */
    public function coffeeTable(): BelongsTo
    {
        return $this->belongsTo(CoffeeTable::class);
    }

    /**
     * The actor who performed the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
