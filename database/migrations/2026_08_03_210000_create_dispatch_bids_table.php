<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('rider_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('note')->nullable();
            $table->string('status')->default('pending'); // pending, accepted, rejected, withdrawn
            $table->timestamps();

            $table->unique(['order_id', 'rider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_bids');
    }
};
