<?php

// database/migrations/2026_10_26_180000_add_firebase_credentials_to_client_apps_table.php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_apps', function (Blueprint $table): void {
            $table->string('firebase_project_id', 120)
                ->nullable()
                ->after('app_key');

            $table->longText('firebase_credentials')
                ->nullable()
                ->after('firebase_project_id');
        });
    }

    public function down(): void
    {
        Schema::table('client_apps', function (Blueprint $table): void {
            $table->dropColumn([
                'firebase_project_id',
                'firebase_credentials',
            ]);
        });
    }
};
