<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table): void {
            $table->string('topic', 100)->nullable()->after('target_os')->index();
            $table->json('payload_data')->nullable()->after('body');
            $table->unsignedInteger('sent_count')->default(0)->after('status');
            $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
            $table->unsignedInteger('delivered_count')->default(0)->after('failed_count');
            $table->unsignedInteger('opened_count')->default(0)->after('delivered_count');
            $table->unsignedInteger('clicked_count')->default(0)->after('opened_count');
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table): void {
            $table->dropColumn([
                'topic',
                'payload_data',
                'sent_count',
                'failed_count',
                'delivered_count',
                'opened_count',
                'clicked_count',
            ]);
        });
    }
};