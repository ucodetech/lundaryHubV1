<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('business_type')->default('sole_proprietorship')->after('is_verified'); // 'cac_registered' or 'sole_proprietorship'
            $table->boolean('is_cac_verified')->default(false)->after('business_type');
            $table->string('kyc_status')->default('pending')->after('is_cac_verified'); // 'pending', 'submitted', 'approved', 'rejected'
        });

        Schema::create('shop_kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->string('document_type'); // 'cac_certificate', 'storefront_photo', 'interior_photo', 'utility_bill', 'owner_id'
            $table->string('file_path');
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_kyc_documents');

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['business_type', 'is_cac_verified', 'kyc_status']);
        });
    }
};
