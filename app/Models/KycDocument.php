<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'rider_profile_id',
        'document_type',
        'file_path',
        'status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'status' => KycStatus::class,
        ];
    }

    public function riderProfile(): BelongsTo
    {
        return $this->belongsTo(RiderProfile::class);
    }
}
