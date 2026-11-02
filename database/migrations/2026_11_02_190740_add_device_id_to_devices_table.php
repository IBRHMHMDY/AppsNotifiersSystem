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
        Schema::table('devices', function (Blueprint $table) {
            // إضافة الحقل كـ String ليحفظ الـ UUID مع وضع Unique Index لضمان عدم التكرار
            $table->string('device_id')->nullable()->after('client_app_id')->unique();

            $table->index(['client_app_id','device_id']);
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }
};
