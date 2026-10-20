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
        Schema::create('app_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_app_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->string('store_url')->nullable(); // رابط جوجل بلاي أو آب ستور
            $table->boolean('is_force_update')->default(false); // هل التحديث إجباري؟
            $table->text('release_notes')->nullable();
            $table->timestamps();

            // نمنع إضافة نفس رقم الإصدار لنفس التطبيق مرتين
            $table->unique(['client_app_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_updates');
    }
};
