<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['id', 'balance'], 'idx_users_id_balance');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['sender_id', 'created_at'], 'idx_transactions_sender_created');
            $table->index(['receiver_id', 'created_at'], 'idx_transactions_receiver_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_id_balance');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_sender_created');
            $table->dropIndex('idx_transactions_receiver_created');
        });
    }
};
