<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            // nullable: إذا كان null يعني إرسال الإشعار لكل التطبيقات
            $table->foreignId('client_app_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('status')->default('pending')->index(); // pending, processing, sent, failed
            $table->string('target_os')->nullable(); // استهداف نظام معين أو null للجميع
            $table->timestamp('scheduled_at')->nullable()->index(); // للجدولة المستقبلية
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
