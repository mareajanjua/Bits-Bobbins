<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'dispatched', 'delivered', 'cancelled', 'returned', 'replacement_requested'])->default('pending');
            $table->enum('delivery_type', ['home_delivery', 'vpp'])->default('home_delivery');
            $table->enum('payment_status', ['pending', 'cleared', 'failed', 'refunded'])->default('pending');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('shipping_name', 100);
            $table->string('shipping_phone', 30);
            $table->string('shipping_address', 255);
            $table->text('return_reason')->nullable();
            $table->timestamp('return_requested_at')->nullable();
            $table->timestamp('ordered_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        // Kept empty so rollback does not delete tables or data.
    }
};
