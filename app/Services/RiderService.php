<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Enums\KycStatus;
use App\Models\KycDocument;
use App\Models\RiderProfile;
use App\Models\User;

class RiderService
{
    public function createProfile(User $user, array $data): RiderProfile
    {
        return RiderProfile::create([
            'user_id' => $user->id,
            'vehicle_type' => $data['vehicle_type'] ?? 'bicycle',
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'is_online' => false,
            'kyc_status' => KycStatus::PENDING,
        ]);
    }

    public function uploadKycDocument(RiderProfile $profile, DocumentType $type, string $filePath): KycDocument
    {
        return KycDocument::updateOrCreate(
            [
                'rider_profile_id' => $profile->id,
                'document_type' => $type,
            ],
            [
                'file_path' => $filePath,
                'status' => KycStatus::PENDING,
                'rejection_reason' => null,
            ]
        );
    }

    public function approveKyc(RiderProfile $profile): RiderProfile
    {
        $profile->update(['kyc_status' => KycStatus::APPROVED]);
        $profile->kycDocuments()->update(['status' => KycStatus::APPROVED]);

        return $profile;
    }

    public function rejectKyc(RiderProfile $profile, string $reason): RiderProfile
    {
        $profile->update(['kyc_status' => KycStatus::REJECTED]);
        $profile->kycDocuments()->update([
            'status' => KycStatus::REJECTED,
            'rejection_reason' => $reason,
        ]);

        return $profile;
    }

    public function toggleOnline(RiderProfile $profile): bool
    {
        $newState = !$profile->is_online;
        $profile->update(['is_online' => $newState]);

        return $newState;
    }
}
