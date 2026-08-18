<?php

namespace Native\Electron\Traits;


use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\warning;

trait CopiesCertificateAuthority
{
    protected function copyCertificateAuthorityCertificate(): void
    {
        try {
            intro('Copying latest CA Certificate...');

            $phpBinaryDirectory = base_path('vendor/nativephp/php-bin/');

            $certificateFileName = 'cacert.pem';
            $certFilePath = rtrim($phpBinaryDirectory, '/\\') . '/' . ltrim($certificateFileName, '/\\');

            if (! file_exists($certFilePath)) {
                warning('CA Certificate not found at '.$certFilePath.'. Skipping copy.');

                return;
            }

            $destPath = rtrim(base_path('vendor/nativephp/electron/resources/js/resources'), '/\\') . '/' . $certificateFileName;
            $copied = copy($certFilePath, $destPath);

            if (! $copied) {
                // It returned false, but doesn't give a reason why.
                throw new \Exception('copy() failed for an unknown reason.');
            }
        } catch (\Throwable $e) {
            error('Failed to copy CA Certificate: '.$e->getMessage());
        }
    }
}
