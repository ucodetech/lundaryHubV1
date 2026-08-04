<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('rider_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedTinyInteger('shop_rating')->default(5); // 1 to 5
            $table->unsignedTinyInteger('rider_rating')->nullable()->default(5); // 1 to 5
            $table->text('shop_comment')->nullable();
            $table->text('rider_comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_reviews');
    }
};
