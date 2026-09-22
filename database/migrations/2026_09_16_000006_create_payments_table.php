<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('method', ['credit_card', 'cheque', 'vpp', 'dd']);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'cleared', 'failed', 'refunded'])->default('pending');
            $table->string('card_holder_name', 100)->nullable();
            $table->char('card_last_four', 4)->nullable();
            $table->string('cheque_number', 50)->nullable();
            $table->string('dd_number', 50)->nullable();
            $table->string('transaction_reference', 120)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // Kept empty so rollback does not delete tables or data.
    }
};
