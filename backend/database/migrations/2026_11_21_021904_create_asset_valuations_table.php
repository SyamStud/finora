<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_valuations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->date('valuation_date');
            $table->decimal('unit_price', 18, 2);
            $table->decimal('total_value', 18, 2);
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->index(['asset_id', 'valuation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_valuations');
    }
};
