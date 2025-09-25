<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medal extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'quantity',
        'price_per_medal',
        'total_amount',
        'status',
        'participants_list',
    ];

    protected $casts = [
        'price_per_medal' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Связь с группой
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Получить статус на русском языке
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'ordered' => 'Заказано',
            'produced' => 'Изготовлено',
            'delivered' => 'Доставлено',
            default => 'Неизвестно'
        };
    }

    /**
     * Получить список участников как массив
     */
    public function getParticipantsArrayAttribute(): array
    {
        if (empty($this->participants_list)) {
            return [];
        }
        
        return array_filter(
            array_map('trim', explode("\n", $this->participants_list))
        );
    }
}
