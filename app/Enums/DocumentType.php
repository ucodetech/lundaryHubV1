<?php

namespace App\Enums;

enum DocumentType: string
{
    case PASSPORT = 'passport';
    case NATIONAL_ID = 'national_id';
    case DRIVERS_LICENSE = 'drivers_license';
    case SELFIE = 'selfie';
    case GUARANTOR = 'guarantor';
    case ADDRESS_PROOF = 'address_proof';

    public function label(): string
    {
        return match ($this) {
            self::PASSPORT => 'Passport Photograph',
            self::NATIONAL_ID => 'National ID / NIN',
            self::DRIVERS_LICENSE => 'Driver\'s License',
            self::SELFIE => 'Live Selfie',
            self::GUARANTOR => 'Guarantor Form',
            self::ADDRESS_PROOF => 'Proof of Address',
        };
    }
}
