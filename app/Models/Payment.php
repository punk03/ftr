<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'type',
        'amount',
        'quantity',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Связь с группой
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Получить статус оплаты на русском языке
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Ожидает оплаты',
            'paid' => 'Оплачено',
            'cancelled' => 'Отменено',
            default => 'Неизвестно'
        };
    }

    /**
     * Получить тип оплаты на русском языке
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'participation' => 'Участие',
            'diploma' => 'Дипломы',
            'medal' => 'Медали',
            default => 'Неизвестно'
        };
    }
}
