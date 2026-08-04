<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            // Paystack Customer
            $table->string('paystack_customer_id')->nullable();
            $table->string('paystack_customer_code')->nullable(); // e.g. CUS_xxxxxxxx

            // Virtual Account Details
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_slug')->nullable();    // e.g. wema-bank
            $table->string('bank_id')->nullable();
            $table->string('paystack_account_id')->nullable(); // Paystack internal DVA id

            // Status
            $table->boolean('is_active')->default(true);
            $table->string('preferred_bank')->default('wema-bank');

            $table->timestamps();

            $table->unique('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_virtual_accounts');
    }
};
