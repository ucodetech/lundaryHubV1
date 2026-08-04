<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected string $cloudName;
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct()
    {
        $cloudinaryUrl = env('CLOUDINARY_URL');

        if ($cloudinaryUrl && preg_match('/cloudinary:\/\/([^:]+):([^@]+)@(.+)/', $cloudinaryUrl, $matches)) {
            $this->apiKey = $matches[1];
            $this->apiSecret = $matches[2];
            $this->cloudName = $matches[3];
        } else {
            $this->cloudName = env('CLOUDINARY_CLOUD_NAME', 'dxm6kfzaq');
            $this->apiKey = env('CLOUDINARY_API_KEY', '766276133854917');
            $this->apiSecret = env('CLOUDINARY_API_SECRET', 'lhIOjkU30t6FApTRsV4qtug2tA8');
        }
    }

    /**
     * Upload an UploadedFile or file path directly to Cloudinary and return secure URL.
     */
    public function upload(UploadedFile|string $file, string $folder = 'laundryhub'): ?string
    {
        try {
            $timestamp = time();
            $paramsToSign = "folder={$folder}&timestamp={$timestamp}";
            $signature = sha1($paramsToSign . $this->apiSecret);

            $endpoint = "https://api.cloudinary.com/v1_1/{$this->cloudName}/auto/upload";

            if ($file instanceof UploadedFile) {
                $fileContents = file_get_contents($file->getRealPath());
                $fileName = $file->getClientOriginalName();
            } else {
                $fileContents = file_get_contents($file);
                $fileName = basename($file);
            }

            $response = Http::attach('file', $fileContents, $fileName)->post($endpoint, [
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
                'folder' => $folder,
                'signature' => $signature,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['secure_url'] ?? null;
            }

            Log::error('Cloudinary Upload Error: ' . $response->body());
            return null;
        } catch (\Throwable $e) {
            Log::error('Cloudinary Exception: ' . $e->getMessage());
            return null;
        }
    }
}
