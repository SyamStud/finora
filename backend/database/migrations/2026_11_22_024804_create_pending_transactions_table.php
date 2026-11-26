<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('source', 30)->default('notification');
            $table->string('package_name')->nullable();
            $table->text('raw_text');
            $table->string('type')->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->uuid('account_id')->nullable();
            $table->string('account_name')->nullable();
            $table->uuid('category_id')->nullable();
            $table->string('category_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('fingerprint')->unique();
            $table->uuid('approved_transaction_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_transactions');
    }
};
