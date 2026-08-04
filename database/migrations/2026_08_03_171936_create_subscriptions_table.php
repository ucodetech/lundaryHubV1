<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shop_id')->nullable()->constrained('shops')->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->onDelete('set null');

            $table->string('role'); // shop_owner, rider
            $table->string('plan_key');
            $table->string('plan_name');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('active');

            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
