<?php

// database/migrations/2026_10_27_130000_add_maintenance_fields_to_client_apps_table.php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_apps', function (Blueprint $table): void {
            $table->boolean('is_under_maintenance')
                ->default(false)
                ->after('is_active');

            $table->text('maintenance_message')
                ->nullable()
                ->after('is_under_maintenance');
        });
    }

    public function down(): void
    {
        Schema::table('client_apps', function (Blueprint $table): void {
            $table->dropColumn([
                'is_under_maintenance',
                'maintenance_message',
            ]);
        });
    }
};
