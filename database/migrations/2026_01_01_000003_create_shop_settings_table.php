<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->unique()->constrained('shops')->onDelete('cascade');
            $table->json('opening_hours')->nullable();
            $table->string('currency')->default('NGN');
            $table->boolean('accepts_pickup')->default(true);
            $table->boolean('accepts_delivery')->default(true);
            $table->decimal('min_order_amount', 10, 2)->default(0.00);
            $table->string('timezone')->default('Africa/Lagos');
            $table->json('branding')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
