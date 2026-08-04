<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('offers_home_delivery')->default(true)->after('delivery_fee');
            $table->boolean('offers_store_pickup')->default(true)->after('offers_home_delivery');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['offers_home_delivery', 'offers_store_pickup']);
        });
    }
};
