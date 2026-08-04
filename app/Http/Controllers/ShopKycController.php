<?php

namespace App\Http\Controllers;

use App\Models\ShopKycDocument;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopKycController extends Controller
{
    public function __construct(protected CloudinaryService $cloudinaryService)
    {
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $shop = $user->ownedShops()->with('kycDocuments')->first();

        if (! $shop) {
            return redirect()->route('shop.create')->with('error', 'Please create a shop before submitting KYC verification.');
        }

        return Inertia::render('Shop/Kyc', [
            'shop' => $shop,
            'kycDocuments' => $shop->kycDocuments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shop = $user->ownedShops()->first();

        if (! $shop) {
            return redirect()->route('shop.create');
        }

        $request->validate([
            'business_type' => 'required|string|in:cac_registered,sole_proprietorship',
            'cac_certificate' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'storefront_photo' => 'nullable|file|mimes:jpeg,png,jpg|max:5120',
            'interior_photo' => 'nullable|file|mimes:jpeg,png,jpg|max:5120',
            'utility_bill' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'owner_id' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        $shop->update([
            'business_type' => $request->business_type,
            'kyc_status' => 'submitted',
        ]);

        $documentTypes = ['cac_certificate', 'storefront_photo', 'interior_photo', 'utility_bill', 'owner_id'];

        foreach ($documentTypes as $docType) {
            if ($request->hasFile($docType)) {
                $file = $request->file($docType);
                $cloudinaryUrl = $this->cloudinaryService->upload($file, "laundryhub/shops/{$shop->id}/kyc");

                if ($cloudinaryUrl) {
                    ShopKycDocument::updateOrCreate(
                        [
                            'shop_id' => $shop->id,
                            'document_type' => $docType,
                        ],
                        [
                            'file_path' => $cloudinaryUrl,
                            'status' => 'pending',
                            'rejection_reason' => null,
                        ]
                    );
                }
            }
        }

        return back()->with('success', 'Shop KYC verification documents uploaded to Cloudinary successfully! Super Admin will audit your store.');
    }
}
