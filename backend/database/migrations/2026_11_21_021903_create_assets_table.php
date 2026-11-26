<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('name');
            $table->string('type');
            $table->string('provider')->nullable();
            $table->decimal('units', 20, 6)->default(0);
            $table->decimal('average_buy_price', 18, 2)->default(0);
            $table->decimal('current_price', 18, 2)->default(0);
            $table->decimal('invested_amount', 18, 2)->default(0);
            $table->decimal('current_value', 18, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
