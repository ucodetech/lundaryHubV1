<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code')->nullable()->unique()->after('phone');
            }
            if (!Schema::hasColumn('users', 'bonus_balance')) {
                $table->decimal('bonus_balance', 10, 2)->default(0.00)->after('referral_code');
            }
            if (!Schema::hasColumn('users', 'referred_by_id')) {
                $table->foreignId('referred_by_id')->nullable()->after('bonus_balance')->constrained('users')->onDelete('set null');
            }
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade');
            $table->string('referral_code');
            $table->string('status')->default('pending'); // pending, rewarded
            $table->string('reward_type')->nullable(); // customer_order, shop_subscription, rider_pass
            $table->decimal('referrer_reward', 10, 2)->default(0.00);
            $table->decimal('referred_reward', 10, 2)->default(0.00);
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bonus_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('type'); // earned_referral, used_on_order, used_on_pass, admin_credit, admin_debit, dispute_refund
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_transactions');
        Schema::dropIfExists('referrals');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropColumn(['referral_code', 'bonus_balance', 'referred_by_id']);
        });
    }
};
