<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->uuid('pocket_id')->nullable()->after('account_id');

            $table->foreign('pocket_id')->references('id')->on('pockets')->restrictOnDelete();
            $table->index(['pocket_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(['pocket_id', 'transaction_date']);
            $table->dropForeign(['pocket_id']);
            $table->dropColumn('pocket_id');
        });
    }
};
