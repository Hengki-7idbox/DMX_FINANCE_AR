<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('detail_sale_items');

        Schema::dropIfExists('detail_sales');

        Schema::create('detail_sales', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['internal', 'external'])->default('external');
            $table->string('customer_code', 50)->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->string('doc_number', 50);
            $table->date('transaction_date')->nullable();
            $table->string('description', 500)->nullable();
            $table->decimal('invoice_amount', 18, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('tax_base', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('return_amount', 18, 2)->default(0);
            $table->decimal('owing', 18, 2)->default(0);
            $table->decimal('payment_amount', 18, 2)->default(0);
            $table->decimal('calculate_field', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['source_type', 'doc_number']);
            $table->index(['source_type', 'transaction_date']);
            $table->index(['source_type', 'customer_code']);
        });

        Schema::create('detail_sale_items', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['internal', 'external'])->default('external');
            $table->string('doc_number', 50);
            $table->string('customer_code', 50)->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('item_no', 100)->nullable();
            $table->string('item_description', 500)->nullable();
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('qty', 18, 2)->default(0);
            $table->string('unit', 50)->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['source_type', 'doc_number']);
            $table->index(['source_type', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_sale_items');
        Schema::dropIfExists('detail_sales');
    }
};
