<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_movements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('asset_id');
            $table->string('type', 20);
            $table->decimal('amount', 18, 2);
            $table->decimal('units', 20, 6);
            $table->decimal('nav', 18, 2);
            $table->date('movement_date');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->index(['asset_id', 'movement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};
