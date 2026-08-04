<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderDispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispute_number',
        'order_id',
        'reporter_id',
        'against_type',
        'reason',
        'subject',
        'description',
        'evidence_photos',
        'status',
        'resolution_notes',
        'resolved_by_id',
        'resolved_at',
    ];

    protected $casts = [
        'evidence_photos' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class, 'dispute_id');
    }
}
