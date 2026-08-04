<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispute_id',
        'user_id',
        'message',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(OrderDispute::class, 'dispute_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
