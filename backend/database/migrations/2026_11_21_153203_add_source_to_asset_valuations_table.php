<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_valuations', function (Blueprint $table): void {
            $table->string('source', 20)->default('manual')->after('total_value');
        });
    }

    public function down(): void
    {
        Schema::table('asset_valuations', function (Blueprint $table): void {
            $table->dropColumn('source');
        });
    }
};
