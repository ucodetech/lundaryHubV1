<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('rider_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('fulfillment_type')->default('store_pickup'); // home_delivery, store_pickup
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->default('cash_on_delivery'); // cash_on_delivery, paystack

            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('delivery_fee', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);

            $table->string('pickup_address', 500)->nullable();
            $table->string('delivery_address', 500)->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();

            $table->boolean('is_legacy')->default(false);
            $table->string('legacy_customer_name')->nullable();
            $table->string('legacy_customer_phone')->nullable();

            $table->text('notes')->nullable();
            $table->string('cancelled_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
