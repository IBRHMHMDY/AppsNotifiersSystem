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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_app_id')->constrained()->cascadeOnDelete();
            $table->string('fcm_token')->unique()->nullable(); // رمز Firebase للإشعارات
            $table->string('os_type')->index(); // Android, iOS, Windows
            $table->string('app_version')->index(); // للتحقق مما إذا كان الجهاز يحتاج لتحديث
            $table->timestamp('last_active_at')->nullable()->index(); // لمعرفة الأجهزة النشطة مؤخراً
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
