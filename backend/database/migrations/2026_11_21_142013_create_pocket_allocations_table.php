<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pocket_allocations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('account_id');
            $table->uuid('source_pocket_id')->nullable();
            $table->uuid('destination_pocket_id')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('note')->nullable();
            $table->timestamp('allocated_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('source_pocket_id')->references('id')->on('pockets')->restrictOnDelete();
            $table->foreign('destination_pocket_id')->references('id')->on('pockets')->restrictOnDelete();
            $table->index(['account_id', 'allocated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pocket_allocations');
    }
};
