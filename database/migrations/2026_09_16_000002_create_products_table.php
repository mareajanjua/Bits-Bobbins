<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->char('product_number', 5);
            $table->char('product_code', 7)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('warranty_days')->nullable();
            $table->timestamps();

            $table->unique(['category_id', 'product_number']);
        });
    }

    public function down()
    {
        // Kept empty so rollback does not delete tables or data.
    }
};
