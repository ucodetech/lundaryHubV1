<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_disputes', function (Blueprint $table) {
            $table->id();
            $table->string('dispute_number')->unique(); // e.g. DISP-80921
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->string('against_type'); // shop, rider, platform
            $table->string('reason'); // damaged_garment, missing_item, late_delivery, overcharge, other
            $table->string('subject');
            $table->text('description');
            $table->json('evidence_photos')->nullable();
            $table->string('status')->default('open'); // open, under_review, resolved_refund, resolved_compensated, resolved_rejected, closed
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained('order_disputes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_messages');
        Schema::dropIfExists('order_disputes');
    }
};
