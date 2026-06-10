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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')
                  ->constrained('wallets')
                  ->cascadeOnDelete();

            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete();

            $table->decimal('amount', 15, 2);
            $table->string('type'); // 'income', 'expense', 'transfer'
            $table->text('description')->nullable();
            $table->datetime('transaction_date');

            $table->foreignId('destination_wallet_id')
                  ->nullable()
                  ->constrained('wallets')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
