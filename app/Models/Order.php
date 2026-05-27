<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'user_id',
        'cashier_id',
        'payment_method',
        'total',
        'cash_received',
        'change_amount',
        'payment_proof',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'cash_received' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Generate unique order number: CK-YYYYMMDD-XXXX
     */
    public static function generateOrderNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = "CK-{$today}-";

        $lastOrder = static::where('order_number', 'like', $prefix . '%')
            ->orderByDesc('order_number')
            ->first();

        $nextNumber = 1;
        if ($lastOrder) {
            $lastNum = (int) substr($lastOrder->order_number, -4);
            $nextNumber = $lastNum + 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'antrian_baru' => 'Antrean Baru',
            'sedang_dibuat' => 'Sedang Dibuat',
            'selesai' => 'Selesai',
            'diambil' => 'Telah Diambil',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'menunggu_verifikasi' => 'bg-orange-100 text-orange-700',
            'antrian_baru' => 'bg-amber-100 text-amber-700',
            'sedang_dibuat' => 'bg-blue-100 text-blue-700',
            'selesai' => 'bg-green-100 text-green-700',
            'diambil' => 'bg-purple-100 text-purple-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
