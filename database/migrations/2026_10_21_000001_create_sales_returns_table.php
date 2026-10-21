<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 50)->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->string('invoice_no', 50)->nullable();
            $table->string('return_no', 50);
            $table->date('return_date')->nullable();
            $table->string('item_no', 50)->nullable();
            $table->string('item_description', 255)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('quantity', 15, 2)->default(0);
            $table->string('unit', 20)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index('return_no');
            $table->index('return_date');
            $table->index('invoice_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
